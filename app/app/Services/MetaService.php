<?php
final class MetaService {
  public static function search(string $q): array {
    // demo mock data
    return [
      [
        'id' => 'demo1',
        'title' => 'Demo Movie',
        'poster' => 'https://image.tmdb.org/t/p/w500/8UlWHLMpgZm9bx6QYh0NFoq67TZ.jpg',
        'year' => '2024'
      ],
      [
        'id' => 'demo2',
        'title' => 'Demo Series',
        'poster' => 'https://image.tmdb.org/t/p/w500/kXfqcdQKsToO0OUXHcrrNCHDBzO.jpg',
        'year' => '2023'
      ]
    ];
  }

  public static function detail(string $id): array {
    return [
      'id' => $id,
      'title' => $id === 'demo1' ? 'Demo Movie' : 'Demo Series',
      'poster' => 'https://image.tmdb.org/t/p/w500/kXfqcdQKsToO0OUXHcrrNCHDBzO.jpg',
      'desc' => 'This is a demo movie/series.',
      'episodes' => [
        ['ep'=>1,'name'=>'EP 1'],
        ['ep'=>2,'name'=>'EP 2']
      ]
    ];
  }
}
