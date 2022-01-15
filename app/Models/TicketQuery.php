<?php

namespace App\Models;

abstract class TicketQuery
{
  protected $req_headers = [
    'allow_redirects' => true,
    'headers'         => [
      'Accept'        => 'application/json',
      'Authorization' => '',
    ],
    'user_agent'      => 'Tickershop fetcher (hp197)',
  ];

  protected function _getJsonData($url)
  {
    $cache = \Config\Services::cache();

    if (($cache_data = $cache->get(md5($url))) !== false)
    {
      return $cache_data;
    }

    $client = \Config\Services::curlrequest();
    $this->req_headers['headers']['Authorization'] = 'Token ' . getenv('ticketshop_apitoken');
    $response = $client->request('GET', $url, $this->req_headers);

    $data = json_decode($response->getBody());
    $cache->save(md5($url), $data, 60);
    return $data;
  }
}

