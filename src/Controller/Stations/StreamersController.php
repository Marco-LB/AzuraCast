<?php
namespace App\Controller\Stations;

use App\Form\EntityForm;
use App\Radio\Backend\AbstractBackend;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

class StreamersController
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $csrf_namespace = 'stations_streamers';

    /** @var Entity\Repository\StationStreamerRepository */
    protected $streamers_repo;

    /** @var EntityForm */
    protected $form;

    /**
     * StreamersController constructor.
     * @param EntityForm $form
     *
     * @see \App\Provider\StationsProvider
     */
    public function __construct(EntityForm $form)
    {
        $this->form = $form;

        $this->em = $form->getEntityManager();
        $this->streamers_repo = $this->em->getRepository(Entity\StationStreamer::class);
    }

    public function indexAction(Request $request, Response $response, $station_id): ResponseInterface
    {
        $station = $request->getStation();
        $backend = $request->getStationBackend();

        if (!$backend::supportsStreamers()) {
            throw new \App\Exception\StationUnsupported;
        }

        $view = $request->getView();

        if (!$station->getEnableStreamers()) {
            if ($request->hasParam('enable')) {
                $station->setEnableStreamers(true);
                $this->em->persist($station);
                $this->em->flush();

                $request->getSession()->flash('<b>' . __('Streamers enabled!') . '</b><br>' . __('You can now set up streamer (DJ) accounts.'),
                    'green');

                return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
            }

            return $view->renderToResponse($response, 'stations/streamers/disabled');
        }

        $be_settings = (array)$station->getBackendConfig();

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        return $view->renderToResponse($response, 'stations/streamers/index', [
            'server_url' => $settings_repo->getSetting(Entity\Settings::BASE_URL, ''),
            'stream_port' => $backend->getStreamPort($station),
            'streamers' => $station->getStreamers(),
            'dj_mount_point' => $be_settings['dj_mount_point'] ?? '/',
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id, $id = null): ResponseInterface
    {
        $station = $request->getStation();
        $this->form->setStation($station);

        $record = (null !== $id)
            ? $this->streamers_repo->findOneBy(['id' => $id, 'station_id' => $station_id])
            : null;

        if (false !== $this->form->process($request, $record)) {
            $this->em->refresh($station);

            $request->getSession()->flash('<b>' . sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Streamer')) . '</b>', 'green');

            return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Streamer'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $station_id, $id, $csrf_token): ResponseInterface
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $station = $request->getStation();

        $record = $this->em->getRepository(Entity\StationStreamer::class)->findOneBy([
            'id' => $id,
            'station_id' => $station_id
        ]);

        if ($record instanceof Entity\StationStreamer) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->em->refresh($station);

        $request->getSession()->flash('<b>' . __('%s deleted.', __('Streamer')) . '</b>', 'green');

        return $response->withRedirect($request->getRouter()->fromHere('stations:streamers:index'));
    }
}
