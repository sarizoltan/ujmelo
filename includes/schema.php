<?php

function schema_local_business(): string {
    $name    = get_setting('site_name',    'Kozmetikus Szalon');
    $address = get_setting('site_address', '');
    $phone   = get_setting('site_phone',   '');
    $email   = get_setting('site_email',   '');

    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'NailSalon',
        'name'       => $name,
        'url'        => BASE_URL,
        'telephone'  => $phone,
        'email'      => $email,
        'address'    => [
            '@type'         => 'PostalAddress',
            'streetAddress' => $address,
        ],
        'priceRange' => '$$',
        'image'      => BASE_URL . '/assets/images/hero-bg.jpg',
        'openingHoursSpecification' => [
            [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday'],
                'opens'     => '09:00',
                'closes'    => '18:00',
            ],
            [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => 'Saturday',
                'opens'     => '09:00',
                'closes'    => '16:00',
            ],
        ],
    ];

    return '<script type="application/ld+json">'
         . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
         . '</script>';
}

function schema_webpage(string $title, string $description, string $url): string {
    $data = [
        '@context'    => 'https://schema.org',
        '@type'       => 'WebPage',
        'name'        => $title,
        'description' => $description,
        'url'         => $url,
        'isPartOf'    => ['@type' => 'WebSite', 'url' => BASE_URL],
    ];

    return '<script type="application/ld+json">'
         . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
         . '</script>';
}

function schema_blog_post(array $post): string {
    $schema = [
        '@context'      => 'https://schema.org',
        '@type'         => 'BlogPosting',
        'headline'      => $post['title']        ?? '',
        'description'   => $post['excerpt']      ?? '',
        'datePublished' => $post['published_at'] ?? '',
        'dateModified'  => $post['updated_at']   ?? $post['published_at'] ?? '',
        'author'        => [
            '@type' => 'Person',
            'name'  => $post['author_name'] ?? get_setting('site_name'),
        ],
        'publisher'     => [
            '@type' => 'Organization',
            'name'  => get_setting('site_name'),
            'url'   => BASE_URL,
        ],
        'url'   => BASE_URL . '/blog/' . ($post['slug'] ?? ''),
        'image' => !empty($post['featured_image'])
            ? UPLOAD_URL . $post['featured_image']
            : BASE_URL . '/assets/images/hero-bg.jpg',
    ];

    return '<script type="application/ld+json">'
         . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
         . '</script>';
}

function schema_service(array $service): string {
    $data = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        'name'        => $service['name']        ?? '',
        'description' => $service['description'] ?? '',
        'provider'    => [
            '@type' => 'NailSalon',
            'name'  => get_setting('site_name'),
        ],
        'offers' => [
            '@type'         => 'Offer',
            'price'         => $service['price'] ?? 0,
            'priceCurrency' => 'HUF',
        ],
    ];

    return '<script type="application/ld+json">'
         . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
         . '</script>';
}