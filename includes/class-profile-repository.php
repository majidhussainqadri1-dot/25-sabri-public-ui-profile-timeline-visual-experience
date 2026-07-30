<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

final class Profile_Repository
{
    public function __construct(
        private Visibility_Policy $visibility,
        private Native_Integration $native
    ) {
    }

    public function get_founder(): ?WP_User
    {
        $user_id = $this->native->founder_user_id();

        return $user_id > 0 ? get_user_by('id', $user_id) ?: null : null;
    }

    public function find_by_slug(string $slug): ?WP_User
    {
        $slug = sanitize_title($slug);
        if ($slug === '' || ctype_digit($slug)) {
            return null;
        }

        $external = apply_filters('sabri_public_experience/profile_by_slug', null, $slug);
        if ($external instanceof WP_User) {
            return $external;
        }

        $user = get_user_by('slug', $slug);

        return $user instanceof WP_User ? $user : null;
    }

    /** @return array<string,mixed>|null */
    public function get_public_profile(WP_User $user): ?array
    {
        if (! $this->visibility->can_render_publicly($user)) {
            return null;
        }

        $user_id = (int) $user->ID;
        $profile_class = $this->visibility->profile_class($user);
        $credentials = $this->native->professional_credentials($user_id);
        $clinic = $this->native->clinic($user_id);
        $founder = $profile_class === 'founder' ? $this->native->founder_profile() : [];

        $photo_id = (int) get_user_meta($user_id, '_spd_profile_photo_id', true);
        $cover_id = (int) get_user_meta($user_id, '_spd_cover_photo_id', true);
        if ($profile_class === 'founder') {
            $photo_id = (int) ($founder['photo_id'] ?? $photo_id);
            $cover_id = (int) ($founder['cover_id'] ?? $cover_id);
        }

        $avatar = $photo_id > 0
            ? wp_get_attachment_image_url($photo_id, 'medium')
            : get_avatar_url($user_id, ['size' => 256]);
        $cover = $cover_id > 0 ? wp_get_attachment_image_url($cover_id, 'large') : '';
        $headline = $profile_class === 'founder'
            ? (string) ($founder['title'] ?? '')
            : (string) ($credentials['specialization'] ?? $this->native->profile_value($user_id, 'specialty'));
        $bio = $profile_class === 'founder'
            ? (string) ($founder['introduction'] ?? '')
            : $this->native->profile_value($user_id, 'bio', $user->description);

        $show_professional_location = in_array($profile_class, ['founder', 'doctor', 'institution'], true);
        $country = $show_professional_location
            ? $this->native->profile_value($user_id, 'country', (string) ($clinic['country'] ?? ''))
            : '';
        $city = $show_professional_location
            ? $this->native->profile_value($user_id, 'city', (string) ($clinic['city'] ?? ''))
            : '';
        $contacts = $this->public_contacts($user_id, $clinic, $founder);
        $available_sections = $this->available_sections($profile_class, $bio, $contacts, $clinic);
        $all_labels = $this->section_labels($profile_class);
        $section_labels = array_intersect_key($all_labels, array_flip($available_sections));
        $default_name = $profile_class === 'founder'
            ? (string) get_option('sabri_public_experience_founder_display_name', 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed')
            : (string) $user->display_name;

        $profile = [
            'slug' => sanitize_title((string) $user->user_nicename),
            'display_name' => $default_name,
            'class' => $profile_class,
            'role_label' => $this->role_label($profile_class),
            'verified' => in_array($profile_class, ['founder', 'doctor'], true),
            'avatar_url' => is_string($avatar) ? $avatar : '',
            'cover_url' => is_string($cover) ? $cover : '',
            'headline' => $headline,
            'bio' => $bio,
            'country' => $country,
            'city' => $city,
            'contacts' => $contacts,
            'canonical_url' => $this->canonical_url($user),
            'available_sections' => $available_sections,
            'section_labels' => $section_labels,
        ];

        /** @var array<string,mixed> $filtered */
        $filtered = (array) apply_filters('sabri_public_experience/public_profile_data', $profile, $user);

        $filtered_name = $this->plain_text((string) ($filtered['display_name'] ?? $profile['display_name']), 190);
        $profile['display_name'] = $filtered_name !== '' ? $filtered_name : $this->plain_text($default_name, 190);
        $profile['headline'] = $this->plain_text((string) ($filtered['headline'] ?? $profile['headline']), 300);
        $profile['bio'] = $this->plain_text((string) ($filtered['bio'] ?? $profile['bio']), 12000);
        $profile['avatar_url'] = esc_url_raw((string) ($filtered['avatar_url'] ?? $profile['avatar_url']));
        $profile['cover_url'] = esc_url_raw((string) ($filtered['cover_url'] ?? $profile['cover_url']));
        $profile['country'] = $this->plain_text((string) $profile['country'], 100);
        $profile['city'] = $this->plain_text((string) $profile['city'], 100);
        $profile['canonical_url'] = esc_url_raw((string) $profile['canonical_url']);

        return $profile;
    }

    public function canonical_url(WP_User $user, string $section = 'overview'): string
    {
        $profile_class = $this->visibility->profile_class($user);
        $slug = rawurlencode(sanitize_title((string) $user->user_nicename));
        $base = match ($profile_class) {
            'founder' => home_url('/founder/'),
            'doctor' => home_url('/doctors/' . $slug . '/'),
            default => home_url('/profile/' . $slug . '/'),
        };

        return $section === 'overview' ? $base : trailingslashit($base . sanitize_key($section));
    }

    /** @return array<string,string> */
    public function section_labels(string $profile_class): array
    {
        return match ($profile_class) {
            'founder' => [
                'overview' => __('Overview', 'sabri-public-experience'),
                'timeline' => __('Timeline', 'sabri-public-experience'),
                'knowledge' => __('Knowledge', 'sabri-public-experience'),
                'books-research' => __('Books and Research', 'sabri-public-experience'),
                'media' => __('Media', 'sabri-public-experience'),
                'clinic-contact' => __('Clinic and Contact', 'sabri-public-experience'),
                'about' => __('About', 'sabri-public-experience'),
            ],
            'doctor' => [
                'overview' => __('Overview', 'sabri-public-experience'),
                'timeline' => __('Timeline', 'sabri-public-experience'),
                'knowledge' => __('Knowledge', 'sabri-public-experience'),
                'media' => __('Media', 'sabri-public-experience'),
                'clinic' => __('Clinic', 'sabri-public-experience'),
                'reviews' => __('Reviews', 'sabri-public-experience'),
                'about' => __('About', 'sabri-public-experience'),
            ],
            default => [
                'overview' => __('Overview', 'sabri-public-experience'),
                'timeline' => __('Public Contributions', 'sabri-public-experience'),
                'about' => __('About', 'sabri-public-experience'),
            ],
        };
    }

    private function role_label(string $profile_class): string
    {
        return match ($profile_class) {
            'founder' => __('Founder — Official', 'sabri-public-experience'),
            'doctor' => __('Institution Verified Doctor', 'sabri-public-experience'),
            'teacher' => __('Teacher', 'sabri-public-experience'),
            'researcher' => __('Researcher', 'sabri-public-experience'),
            'student' => __('Student', 'sabri-public-experience'),
            'patient' => __('Patient', 'sabri-public-experience'),
            'pharmacy' => __('Pharmacy', 'sabri-public-experience'),
            'institution' => __('Clinic or Institution', 'sabri-public-experience'),
            'publisher' => __('Publisher', 'sabri-public-experience'),
            default => __('Member', 'sabri-public-experience'),
        };
    }

    /**
     * @param array<string,mixed> $clinic
     * @param array<string,mixed> $founder
     * @return array<string,string>
     */
    private function public_contacts(int $user_id, array $clinic, array $founder): array
    {
        $values = [
            'phone' => (string) ($founder['phone'] ?? $clinic['phone'] ?? $this->native->profile_value($user_id, 'phone')),
            'whatsapp' => (string) ($founder['whatsapp'] ?? $clinic['whatsapp'] ?? $this->native->profile_value($user_id, 'whatsapp')),
        ];
        $contacts = $this->sanitize_contacts($values, $user_id);

        /** @var array<string,mixed> $filtered */
        $filtered = (array) apply_filters('sabri_public_experience/public_contacts', $contacts, $user_id);

        return $this->sanitize_contacts($filtered, $user_id);
    }

    /** @param array<string,mixed> $values @return array<string,string> */
    private function sanitize_contacts(array $values, int $user_id): array
    {
        $contacts = [];
        foreach (['phone', 'whatsapp'] as $field) {
            $value = isset($values[$field]) && is_scalar($values[$field]) ? (string) $values[$field] : '';
            $clean = preg_replace('/[^0-9+]/', '', $value) ?? '';
            $clean = preg_replace('/(?!^)\+/', '', $clean) ?? '';
            $clean = substr($clean, 0, 18);
            if ($clean === '' || $clean === '+') {
                continue;
            }
            if ($this->visibility->can_show_contact($user_id, $field)) {
                $contacts[$field] = $clean;
            }
        }

        return $contacts;
    }

    /**
     * Return only sections that File 25 can render truthfully in this phase.
     * Filters may hide an existing section, but may not create a dead tab. A
     * later section-provider registry will own additive knowledge/media tabs.
     *
     * @param array<string,string> $contacts
     * @param array<string,mixed> $clinic
     * @return list<string>
     */
    private function available_sections(string $profile_class, string $bio, array $contacts, array $clinic): array
    {
        $sections = ['overview', 'timeline'];
        if (trim(wp_strip_all_tags($bio)) !== '') {
            $sections[] = 'about';
        }
        if ($profile_class === 'founder' && ($contacts !== [] || $clinic !== [])) {
            $sections[] = 'clinic-contact';
        }
        if ($profile_class === 'doctor' && ($contacts !== [] || $clinic !== [])) {
            $sections[] = 'clinic';
        }

        $filtered = (array) apply_filters(
            'sabri_public_experience/available_profile_sections',
            $sections,
            $profile_class
        );
        $clean = [];
        foreach ($filtered as $section) {
            if (! is_scalar($section)) {
                continue;
            }
            $section = sanitize_key((string) $section);
            if (in_array($section, $sections, true)) {
                $clean[] = $section;
            }
        }
        if (! in_array('overview', $clean, true)) {
            array_unshift($clean, 'overview');
        }

        return array_values(array_unique($clean));
    }

    private function plain_text(string $value, int $limit): string
    {
        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value) ?? $value;
        $value = sanitize_textarea_field($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return function_exists('mb_substr') ? mb_substr(trim($value), 0, $limit) : substr(trim($value), 0, $limit);
    }
}
