<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

final class Timeline_Service
{
    public function __construct(private Timeline_Registry $registry)
    {
    }

    /**
     * @param array<string, mixed> $query
     * @return array{items:list<array<string,mixed>>,page:int,per_page:int,has_more:bool,provider_errors:list<string>}
     */
    public function get_for_author(int $author_id, array $query = []): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $per_page = min(50, max(1, (int) ($query['per_page'] ?? 20)));
        $content_type = sanitize_key((string) ($query['content_type'] ?? ''));
        $provider_filter = sanitize_key((string) ($query['provider'] ?? ''));
        $items = [];
        $errors = [];

        foreach ($this->registry->all() as $provider_id => $provider) {
            if ($provider_filter !== '' && $provider_filter !== $provider_id) {
                continue;
            }
            if (! $provider->is_available() || $provider->get_maturity_level() === 'disabled') {
                continue;
            }

            try {
                foreach ($provider->get_public_author_items($author_id, $query) as $item) {
                    if ($content_type !== '' && $item->get('content_type') !== $content_type) {
                        continue;
                    }
                    $key = $item->get('provider_id') . ':' . $item->get('native_object_type') . ':' . $item->get('native_object_id');
                    $items[$key] = $item;
                }
            } catch (\Throwable $exception) {
                $errors[] = $provider_id;
                do_action('sabri_public_experience/provider_error', $provider_id, $exception);
            }
        }

        usort(
            $items,
            static function (Normalized_Timeline_Item $left, Normalized_Timeline_Item $right): int {
                $pin = (int) $right->get('pin_weight') <=> (int) $left->get('pin_weight');
                if ($pin !== 0) {
                    return $pin;
                }
                return strcmp((string) $right->get('published_at'), (string) $left->get('published_at'));
            }
        );

        $offset = ($page - 1) * $per_page;
        $slice = array_slice($items, $offset, $per_page + 1);
        $has_more = count($slice) > $per_page;
        if ($has_more) {
            array_pop($slice);
        }

        return [
            'items' => array_map(static fn (Normalized_Timeline_Item $item): array => $item->to_array(), array_values($slice)),
            'page' => $page,
            'per_page' => $per_page,
            'has_more' => $has_more,
            'provider_errors' => array_values(array_unique($errors)),
        ];
    }
}
