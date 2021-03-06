<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use App\Radio\Backend\Liquidsoap;
use Psr\Http\Message\UriInterface;

/**
 * @ORM\Table(name="station_media", indexes={
 *   @ORM\Index(name="search_idx", columns={"title", "artist", "album"})
 * }, uniqueConstraints={
 *   @ORM\UniqueConstraint(name="path_unique_idx", columns={"path", "station_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Entity\Repository\StationMediaRepository")
 */
class StationMedia
{
    use Traits\UniqueId, Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="media")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="song_id", type="string", length=50, nullable=true)
     * @var string|null
     */
    protected $song_id;

    /**
     * @ORM\ManyToOne(targetEntity="Song")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="song_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     * @var Song|null
     */
    protected $song;

    /**
     * @ORM\Column(name="title", type="string", length=200, nullable=true)
     * @var string|null
     */
    protected $title;

    /**
     * @ORM\Column(name="artist", type="string", length=200, nullable=true)
     * @var string|null
     */
    protected $artist;

    /**
     * @ORM\Column(name="album", type="string", length=200, nullable=true)
     * @var string|null
     */
    protected $album;

    /**
     * @ORM\Column(name="lyrics", type="text", nullable=true)
     * @var string|null
     */
    protected $lyrics;

    /**
     * @ORM\Column(name="isrc", type="string", length=15, nullable=true)
     * @var string|null The track ISRC (International Standard Recording Code), used for licensing purposes.
     */
    protected $isrc;

    /**
     * @ORM\Column(name="length", type="integer")
     * @var int
     */
    protected $length;

    /**
     * @ORM\Column(name="length_text", type="string", length=10, nullable=true)
     * @var string|null
     */
    protected $length_text;

    /**
     * @ORM\Column(name="path", type="string", length=500, nullable=true)
     * @var string|null
     */
    protected $path;

    /**
     * @ORM\Column(name="mtime", type="integer", nullable=true)
     * @var int|null
     */
    protected $mtime;

    /**
     * @ORM\Column(name="fade_overlap", type="decimal", precision=3, scale=1, nullable=true)
     * @var float|null
     */
    protected $fade_overlap;

    /**
     * @ORM\Column(name="fade_in", type="decimal", precision=3, scale=1, nullable=true)
     * @var float|null
     */
    protected $fade_in;

    /**
     * @ORM\Column(name="fade_out", type="decimal", precision=3, scale=1, nullable=true)
     * @var float|null
     */
    protected $fade_out;

    /**
     * @ORM\Column(name="cue_in", type="decimal", precision=5, scale=1, nullable=true)
     * @var float|null
     */
    protected $cue_in;

    /**
     * @ORM\Column(name="cue_out", type="decimal", precision=5, scale=1, nullable=true)
     * @var float|null
     */
    protected $cue_out;

    /**
     * @ORM\OneToMany(targetEntity="StationPlaylistMedia", mappedBy="media")
     * @var Collection
     */
    protected $playlist_items;

    /**
     * @ORM\OneToMany(targetEntity="StationMediaCustomField", mappedBy="media")
     * @var Collection
     */
    protected $custom_fields;

    public function __construct(Station $station, string $path)
    {
        $this->station = $station;

        $this->length = 0;
        $this->length_text = '0:00';

        $this->mtime = 0;

        $this->playlist_items = new ArrayCollection;
        $this->custom_fields = new ArrayCollection;

        $this->setPath($path);
        $this->generateUniqueId();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return Song|null
     */
    public function getSong(): ?Song
    {
        return $this->song;
    }

    /**
     * @param Song|null $song
     */
    public function setSong(Song $song = null): void
    {
        $this->song = $song;
    }

    /**
     * @return string|null
     */
    public function getSongId(): ?string
    {
        return $this->song_id;
    }

    /**
     * Check if the hash of the associated Song record matches the hash that would be
     *   generated by this record's artist and title metadata. Used to determine if a
     *   record should be reprocessed or not.
     *
     * @return bool
     */
    public function songMatches(): bool
    {
        return (null !== $this->song_id)
            && ($this->song_id === $this->getExpectedSongHash());
    }

    /**
     * Get the appropriate song hash for the title and artist specified here.
     *
     * @return string
     */
    protected function getExpectedSongHash(): string
    {
        return Song::getSongHash([
            'artist' => $this->artist,
            'title' => $this->title,
        ]);
    }

    /**
     * @return null|string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param null|string $title
     */
    public function setTitle(string $title = null): void
    {
        $this->title = $this->_truncateString($title, 200);
    }

    /**
     * @return null|string
     */
    public function getArtist(): ?string
    {
        return $this->artist;
    }

    /**
     * @param null|string $artist
     */
    public function setArtist(string $artist = null): void
    {
        $this->artist = $this->_truncateString($artist, 200);
    }

    /**
     * @return null|string
     */
    public function getAlbum(): ?string
    {
        return $this->album;
    }

    /**
     * @param null|string $album
     */
    public function setAlbum(string $album = null): void
    {
        $this->album = $this->_truncateString($album, 200);
    }

    /**
     * @return null|string
     */
    public function getLyrics(): ?string
    {
        return $this->lyrics;
    }

    /**
     * @param null|string $lyrics
     */
    public function setLyrics($lyrics): void
    {
        $this->lyrics = $lyrics;
    }

    /**
     * Get the Flysystem URI for album artwork for this item.
     *
     * @return string
     */
    public function getArtPath(): string
    {
        return 'albumart://'.$this->unique_id.'.jpg';
    }

    /**
     * @return null|string
     */
    public function getIsrc(): ?string
    {
        return $this->isrc;
    }

    /**
     * @param null|string $isrc
     */
    public function setIsrc(string $isrc = null): void
    {
        $this->isrc = $isrc;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength($length): void
    {
        $length_min = floor($length / 60);
        $length_sec = $length % 60;

        $this->length = (int)round($length);
        $this->length_text = $length_min . ':' . str_pad($length_sec, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return null|string
     */
    public function getLengthText(): ?string
    {
        return $this->length_text;
    }

    /**
     * @param null|string $length_text
     */
    public function setLengthText(string $length_text = null): void
    {
        $this->length_text = $length_text;
    }

    /**
     * @return null|string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param null|string $path
     */
    public function setPath(string $path = null): void
    {
        $this->path = $path;
    }

    /**
     * Return the abstracted "full path" filesystem URI for this record.
     *
     * @return string
     */
    public function getPathUri(): string
    {
        return 'media://'.$this->path;
    }

    /**
     * @return int|null
     */
    public function getMtime(): ?int
    {
        return $this->mtime;
    }

    /**
     * @param int|null $mtime
     */
    public function setMtime(int $mtime = null): void
    {
        $this->mtime = $mtime;
    }

    /**
     * @return float|null
     */
    public function getFadeOverlap(): ?float
    {
        return $this->fade_overlap;
    }

    /**
     * @param float|null $fade_overlap
     */
    public function setFadeOverlap($fade_overlap = null): void
    {
        if ($fade_overlap === '') {
            $fade_overlap = null;
        }

        $this->fade_overlap = $fade_overlap;
    }

    /**
     * @return float|null
     */
    public function getFadeIn(): ?float
    {
        return $this->fade_in;
    }

    /**
     * @param float|null $fade_in
     */
    public function setFadeIn($fade_in = null): void
    {
        if ($fade_in === '') {
            $fade_in = null;
        }

        $this->fade_in = $fade_in;
    }

    /**
     * @return float|null
     */
    public function getFadeOut(): ?float
    {
        return $this->fade_out;
    }

    /**
     * @param float|null $fade_out
     */
    public function setFadeOut($fade_out = null): void
    {
        if ($fade_out === '') {
            $fade_out = null;
        }

        $this->fade_out = $fade_out;
    }

    /**
     * @return float|null
     */
    public function getCueIn(): ?float
    {
        return $this->cue_in;
    }

    /**
     * @param float|null $cue_in
     */
    public function setCueIn($cue_in = null): void
    {
        if ($cue_in === '') {
            $cue_in = null;
        }

        $this->cue_in = $cue_in;
    }

    /**
     * @return float|null
     */
    public function getCueOut(): ?float
    {
        return $this->cue_out;
    }

    /**
     * @param float|null $cue_out
     */
    public function setCueOut($cue_out = null): void
    {
        if ($cue_out === '') {
            $cue_out = null;
        }

        $this->cue_out = $cue_out;
    }

    /**
     * Get the length with cue-in and cue-out points included.
     *
     * @return int
     */
    public function getCalculatedLength(): int
    {
        $length = (int)$this->length;

        if ((int)$this->cue_out > 0) {
            $length_removed = $length - (int)$this->cue_out;
            $length -= $length_removed;
        }
        if ((int)$this->cue_in > 0) {
            $length -= $this->cue_in;
        }

        return $length;
    }

    /**
     * @return Collection
     */
    public function getPlaylistItems(): Collection
    {
        return $this->playlist_items;
    }

    /**
     * @param StationPlaylist $playlist
     * @return StationPlaylistMedia|null
     */
    public function getItemForPlaylist(StationPlaylist $playlist): ?StationPlaylistMedia
    {
        $item = $this->playlist_items->filter(function($spm) use ($playlist) {
            /** @var StationPlaylistMedia $spm */
            return $spm->getPlaylist()->getId() === $playlist->getId();
        });

        return $item->first() ?? null;
    }

    /**
     * @return Collection
     */
    public function getCustomFields(): Collection
    {
        return $this->custom_fields;
    }

    /**
     * @param Collection $custom_fields
     */
    public function setCustomFields(Collection $custom_fields): void
    {
        $this->custom_fields = $custom_fields;
    }

    /**
     * Indicate whether this media needs reprocessing given certain factors.
     *
     * @param int $current_mtime
     * @return bool
     */
    public function needsReprocessing($current_mtime = 0): bool
    {
        if ($current_mtime > $this->mtime) {
            return true;
        }
        if (!$this->songMatches()) {
            return true;
        }
        return false;
    }

    /**
     * Assemble a list of annotations for LiquidSoap.
     *
     * Liquidsoap expects a string similar to:
     *     annotate:type="song",album="$ALBUM",display_desc="$FULLSHOWNAME",
     *     liq_start_next="2.5",liq_fade_in="3.5",liq_fade_out="3.5":$SONGPATH
     *
     * @return array
     */
    public function getAnnotations(): array
    {
        $annotations = [];
        $annotation_types = [
            'title'         => $this->title,
            'artist'        => $this->artist,
            'duration'      => $this->length,
            'song_id'       => $this->getSong()->getId(),
            'media_id'      => $this->id,
            'liq_start_next' => $this->fade_overlap,
            'liq_fade_in'   => $this->fade_in,
            'liq_fade_out'  => $this->fade_out,
            'liq_cue_in'    => $this->cue_in,
            'liq_cue_out'   => $this->cue_out,
        ];

        foreach ($annotation_types as $annotation_name => $prop) {
            if (null === $prop) {
                continue;
            }

            $prop = mb_convert_encoding($prop, 'UTF-8');
            $prop = str_replace(['"', "\n", "\t", "\r"], ["'", '', '', ''], $prop);

            if ('liq_cue_out' === $annotation_name && $prop < 0) {
                $prop = max(0, $this->getLength() - abs($prop));
            }

            // Convert Liquidsoap-specific annotations to floats.
            if ('duration' === $annotation_name || 0 === strpos($annotation_name, 'liq')) {
                $prop = Liquidsoap::toFloat($prop);
            }

            $annotations[$annotation_name] = $prop;
        }

        $annotations['genre'] = $this->id;

        return $annotations;
    }

    /**
     * Indicates whether this media is a part of any "requestable" playlists.
     *
     * @return bool
     */
    public function isRequestable(): bool
    {
        $playlists = $this->getPlaylistItems();
        foreach($playlists as $playlist_item) {
            $playlist = $playlist_item->getPlaylist();
            /** @var StationPlaylist $playlist */
            if ($playlist->isRequestable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param \App\ApiUtilities $api_utils
     * @param UriInterface|null $base_url
     * @return Api\Song
     */
    public function api(\App\ApiUtilities $api_utils, UriInterface $base_url = null): Api\Song
    {
        $response = new Api\Song;
        $response->id = (string)$this->song_id;
        $response->text = $this->artist . ' - ' . $this->title;
        $response->artist = (string)$this->artist;
        $response->title = (string)$this->title;

        $response->album = (string)$this->album;
        $response->lyrics = (string)$this->lyrics;

        $response->art = $api_utils->getAlbumArtUrl($this->station_id, $this->unique_id, $base_url);
        $response->custom_fields = $api_utils->getCustomFields($this->id);

        return $response;
    }
}
