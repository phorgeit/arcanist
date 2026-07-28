<?php

final class PhutilGitHubReleaseDownloader extends Phobject {

  private $repoURI;
  private $targetPath;
  private $version = 'latest';
  private $hashes = array();
  private $algorithm = 'md5';

  private $downloadFormat = 'zip';

  public function __construct(string $repo_uri, string $target_path) {
    $this->repoURI = rtrim($repo_uri, '/');
    $this->targetPath = $target_path;
  }

  public function setVersion(string $version) {
    $this->version = $version;
    return $this;
  }

  public function validateDownload(array $hashes, string $algo = 'md5') {
    $this->hashes = $hashes;
    $this->algorithm = $algo;
    return $this;
  }

  /**
   * Set the download format.
   * Note that GitHub only supports zip and tar.gz.
   *
   * @param string $format
   * @return $this
   */
  public function setDownloadFormat(string $format) {
    $this->downloadFormat = $format;
    return $this;
  }

  public function download() {
    // Skip downloading if the file already exists and matches the hash.
    if (Filesystem::pathExists($this->targetPath)) {
      if ($this->hashes) {
        $hash = hash_file($this->algorithm, $this->targetPath);
        if (in_array($hash, $this->hashes, true)) {
          return;
        }
      } else {
        return;
      }
    }

    $download_path = new TempFile();

    // HTTPSFuture::setDownloadPath refuses to overwrite.
    Filesystem::remove($download_path);

    $extension = '.'.$this->downloadFormat;

    if ($this->version === 'latest') {
      $uri = $this->repoURI.'/archive/refs/heads/master'.$extension;
    } else {
      $uri = $this->repoURI.'/archive/refs/tags/v'.$this->version.$extension;
    }

    id(new HTTPSFuture($uri))
      ->setDownloadPath($download_path)
      ->resolvex();

    $actual_hash = hash_file($this->algorithm, $download_path);

    if ($this->hashes && !in_array($actual_hash, $this->hashes, true)) {
      $expected = implode(', ', $this->hashes);

      throw new Exception(
        pht(
          'Downloaded hash does not match: expected any of %s, got %s.',
          $expected,
          $actual_hash));
    }

    Filesystem::rename($download_path, $this->targetPath);
  }

}
