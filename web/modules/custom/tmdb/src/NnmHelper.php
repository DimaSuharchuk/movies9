<?php

namespace Drupal\tmdb;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class NnmHelper {

  /**
   * @var \GuzzleHttp\Client|null
   */
  private ?Client $httpClient;

  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Get info about all torrent found by search string.
   *
   * @param string $search_string
   *   String for search on nnm-club site.
   *
   * @return array
   *   Array of info about torrents: "topic_id (like node ID)", "title", "file
   *   size" and
   *   "seeders".
   */
  public function getAllTorrentsData(string $search_string): array {
    $results = [];

    // Fetch all nnm results for the movie.
    $response = $this->httpClient->get('https://nnmclub.to/forum/tracker.php', [
      'query' => [
        'nm' => $search_string, // search string: "title + year"
        'o' => 10, // sort by Seeders
        's' => 2, // sorting DESC
        'sha' => 0, // disable Author column
        'shr' => 1, // enable Rating column
      ],
    ]);

    // Prepare and parse the response.
    $html = $this->prepareNnmHtml($response);
    preg_match_all('/<a class="genmed topictitle.+href="viewtopic\.php\?t=(?<topic_id>\d+)"><b>(?<title>.+)<\/b>.+<td.+<u>\d+<\/u>\s*?(?<size>.+)<\/td>.+"seedmed".+<b>(?<seeders>\d+)<\/b>/U', $html, $matches, PREG_SET_ORDER);

    if ($matches) {
      foreach ($matches as $match) {
        $results[$match['topic_id']] = array_filter($match, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);
      }
    }

    return $results;
  }

  /**
   * Get link to torrent and magnet link.
   *
   * @param int $topic_id
   *   Nnm topic (like node) ID.
   *
   * @return array
   *   Array with info: "torrent_link" and "magnet_link".
   */
  public function getTorrentMagnet(int $topic_id): array {
    $response = $this->httpClient->get('https://nnmclub.to/forum/viewtopic.php', ['query' => ['t' => $topic_id]]);

    // Prepare and parse the response.
    $html = $this->prepareNnmHtml($response);

    // Fetch info from every page.
    $results = [];
    (preg_match('/<a class="maintitle" href="viewtopic\.php\?t=(?<topic_id>\d+)">/', $html, $match));
    if (!empty($match['topic_id'])) {
      (preg_match('/<a href="download\.php\?id=(?<torrent_id>\d+)" rel="nofollow">Скачать<\/a>/', $html, $match)) && $results['torrent_link'] = 'https://nnmclub.to/forum/download.php?id=' . $match['torrent_id'];
      (preg_match('/<a rel="nofollow" href="(?<magnet>magnet:\?xt=urn:btih:\w+)"/', $html, $match)) && $results['magnet_link'] = $match['magnet'];
    }

    return $results;
  }

  /**
   * Prepare and convert nnm-club html to correct encoding.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   Guzzle response from nnm-club.
   *
   * @return string
   *   Html.
   */
  private function prepareNnmHtml(ResponseInterface $response): string {
    $html = $response->getBody()->getContents();

    return str_replace([
      "\r",
      "\n",
      "\t",
    ], '', mb_convert_encoding($html, 'UTF-8', 'windows-1251'));
  }

}
