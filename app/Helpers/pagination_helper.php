<?php

if (!function_exists('custom_pagination_with_query')) {
    function custom_pagination_with_query($pager, array $queryParams = [], $group = 'default', $template = 'default_full')
    {
        // Render pagination links
        $pagerLinks = $pager->links($group, $template);

        // Sisipkan query string ke setiap href
        $pagerLinks = preg_replace_callback(
            '/href="([^"]+)"/',
            function ($matches) use ($queryParams) {
                $url = $matches[1];
                $separator = (strpos($url, '?') !== false) ? '&' : '?';
                return 'href="' . $url . $separator . http_build_query($queryParams) . '"';
            },
            $pagerLinks
        );

        return $pagerLinks;
    }
}
