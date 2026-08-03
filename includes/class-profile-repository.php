<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_User;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

final class Profile_Repository
{
    public const FOUNDER_DISPLAY_NAME = 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed';

    private const MEDIA_OWNER_META = '_spd_media_owner_user_id';
    private const MEDIA_PURPOSE_META = '_spd_media_purpose';
    private const ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_IMAGE_PIXELS = 40000000;

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
        $clinic_source = $this->native->clinic($user_id);
        $founder_source = $profile_class === 'founder' ? $this->native->founder_profile() : [];
        $founder_details = $profile_class === 'founder'
            ? Profile_Data::founder_details($founder_source)
            : [];
        $professional = $profile_class === 'doctor'
            ? Profile_Data::professional_details($credentials)
            : [];
        $clinic = Profile_Data::clinic($clinic_source);

        $photo_id = (int) get_user_meta($user_id, '_spd_profile_photo_id', true);
        $cover_id = (int) get_user_meta($user_id, '_spd_cover_photo_id', true);
        if ($profile_class === 'founder') {
            $photo_id = (int) ($founder_source['photo_id'] ?? $photo_id);
            $cover_id = (int) ($founder_source['cover_id'] ?? $cover_id);
        }

        // File 03 owns presentation media. File 25 projects a local image only
        // when File 03 ownership, purpose, attachment type, MIME, dimensions,
        // and same-site delivery all agree. Every mismatch fails closed.
        $avatar = $this->owned_media_url($photo_id, $user_id, 'profile', 'medium');
        $cover = $this->owned_media_url($cover_id, $user_id, 'cover', 'large');
        $headline = $profile_class === 'founder'
            ? (string) ($founder_source['title'] ?? '')
            : (string) ($professional['specialization'] ?? $this->native->profile_value($user_id, 'specialty'));
        $bio = $profile_class === 'founder'
            ? (string) ($founder_source['introduction'] ?? '')
            : $this->native->profile_value($user_id, 'bio', $user->description);

        $show_professional_location = in_array($profile_class, ['founder', 'doctor', 'institution'], true);
        $country = $show_professional_location
            ? $this->native->profile_value($user_id, 'country', (string) ($clinic['country'] ?? ''))
            : '';
        $city = $show_professional_location
            ? $this->native->profile_value($user_id, 'city', (string) ($clinic['city'] ?? ''))
            : '';
        $location_text = $profile_class === 'founder'
            ? (string) ($founder_details['location'] ?? '')
            : '';
        $contacts = $this->public_contacts($user_id, $clinic_source, $founder_source);
        $available_sections = $this->available_sections(
            $profile_class,
            $bio,
            $contacts,
            $clinic,
            $founder_details,
            $professional
        );
        $all_labels = $this->section_labels($profile_class);
        $section_labels = array_intersect_key($all_labels, array_flip($available_sections));
        $default_name = $profile_class === 'founder'
            ? self::FOUNDER_DISPLAY_NAME
            : (string) $user->display_name;

        $profile = [
            'slug' => sanitize_title((string) $user->user_nicename),
            'display_name' => $default_name,
            'class' => $profile_class,
            'role_label' => $this->role_label($profile_class),
            'verified' => in_array($profile_class, ['founder', 'doctor'], true),
            'avatar_url' => $avatar,
            'cover_url' => $cover,
            'headline' => $headline,
            'bio' => $bio,
            'country' => $country,
            'city' => $city,
            'location_text' => $location_text,
            'contacts' => $contacts,
            'canonical_url' => $this->canonical_url($user),
            'available_sections' => $available_sections,
            'section_labels' => $section_labels,
            'founder_details' => $founder_details,
            'professional' => $professional,
            'clinic' => $clinic,
        ];

        /** @var array<string,mixed> $filtered */
        $filtered = (array) apply_filters('sabri_public_experience/public_profile_data', $profile, $user);

        // Public identity and policy fields remain authoritative. Extensions may
        // modify bounded presentation text and local presentation media only.
        if ($profile_class === 'founder') {
            $profile['display_name'] = self::FOUNDER_DISPLAY_NAME;
        } else {
            $filtered_name = $this->plain_text((string) ($filtered['display_name'] ?? $profile['display_name']), 190);
            $profile['display_name'] = $filtered_name !== '' ? $filtered_name : $this->plain_text($default_name, 190);
        }
        $profile['headline'] = $this->plain_text((string) ($filtered['headline'] ?? $profile['headline']), 300);
        $profile['bio'] = $this->plain_text((string) ($filtered['bio'] ?? $profile['bio']), 12000);
        $profile['avatar_url'] = Public_URL::sanitize_same_site(
            $filtered['avatar_url'] ?? $profile['avatar_url'],
            false
        );
        $profile['cover_url'] = Public_URL::sanitize_same_site(
            $filtered['cover_url'] ?? $profile['cover_url'],
            false
        );
        $profile['country'] = $this->plain_text((string) $profile['country'], 100);
        $profile['city'] = $this->plain_text((string) $profile['city'], 100);
        $profile['location_text'] = $this->plain_text((string) $profile['location_text'], 240);
        $profile['canonical_url'] = Public_URL::sanitize_same_site($profile['canonical_url'], false);

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
     * Return only sections File 25 can render truthfully now. Filters may hide
     * an existing section, but cannot create a dead destination.
     *
     * @param array<string,string> $contacts
     * @param array<string,string> $clinic
     * @param array<string,mixed> $founder_details
     * @param array<string,mixed> $professional
     * @return list<string>
     */
    private function available_sections(
        string $profile_class,
        string $bio,
        array $contacts,
        array $clinic,
        array $founder_details,
        array $professional
    ): array {
        $sections = ['overview', 'timeline'];
        if (
            trim(wp_strip_all_tags($bio)) !== ''
            || $professional !== []
            || array_intersect_key($founder_details, array_flip(['objectives', 'methodology', 'experience'])) !== []
        ) {
            $sections[] = 'about';
        }
        if (
            $profile_class === 'founder'
            && (! empty($founder_details['publications']) || ! empty($founder_details['research']))
        ) {
            $sections[] = 'books-research';
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

    private function owned_media_url(int $attachment_id, int $user_id, string $purpose, string $size): string
    {
        if ($attachment_id <= 0 || $user_id <= 0) {
            return '';
        }
        if (get_post_type($attachment_id) !== 'attachment'
            || ! wp_attachment_is_image($attachment_id)
            || (int) get_post_meta($attachment_id, self::MEDIA_OWNER_META, true) !== $user_id
            || sanitize_key((string) get_post_meta($attachment_id, self::MEDIA_PURPOSE_META, true)) !== $purpose
        ) {
            return '';
        }

        $mime = strtolower((string) get_post_mime_type($attachment_id));
        if (! in_array($mime, self::ALLOWED_IMAGE_MIMES, true)) {
            return '';
        }

        $metadata = wp_get_attachment_metadata($attachment_id);
        if (! is_array($metadata)) {
            return '';
        }
        $width = (int) ($metadata['width'] ?? 0);
        $height = (int) ($metadata['height'] ?? 0);
        if ($width <= 0 || $height <= 0 || $width * $height > self::MAX_IMAGE_PIXELS) {
            return '';
        }

        $url = wp_get_attachment_image_url($attachment_id, $size);

        return Public_URL::sanitize_same_site(is_string($url) ? $url : '', false);
    }

    private function plain_text(string $value, int $limit): string
    {
        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value) ?? $value;
        $value = sanitize_textarea_field($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return function_exists('mb_substr') ? mb_substr(trim($value), 0, $limit) : substr(trim($value), 0, $limit);
    }
}
