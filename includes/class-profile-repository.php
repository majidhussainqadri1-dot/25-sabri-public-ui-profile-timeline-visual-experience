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

    /** @return array<string, mixed>|null */
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

        $avatar = $photo_id > 0 ? wp_get_attachment_image_url($photo_id, 'medium') : get_avatar_url($user_id, ['size' => 256]);
        $cover = $cover_id > 0 ? wp_get_attachment_image_url($cover_id, 'large') : '';
        $headline = $profile_class === 'founder'
            ? (string) ($founder['title'] ?? '')
            : (string) ($credentials['specialization'] ?? $this->native->profile_value($user_id, 'specialty'));
        $bio = $profile_class === 'founder'
            ? (string) ($founder['introduction'] ?? '')
            : $this->native->profile_value($user_id, 'bio', $user->description);

        $profile = [
            'id' => $user_id,
            'slug' => $user->user_nicename,
            'display_name' => $profile_class === 'founder'
                ? (string) apply_filters(
                    'sabri_public_experience/founder_display_name',
                    get_option('sabri_public_experience_founder_display_name', 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed')
                )
                : $user->display_name,
            'class' => $profile_class,
            'role_label' => $this->role_label($profile_class),
            'verified' => in_array($profile_class, ['founder', 'doctor'], true),
            'avatar_url' => is_string($avatar) ? $avatar : '',
            'cover_url' => is_string($cover) ? $cover : '',
            'headline' => $headline,
            'bio' => $bio,
            'country' => $this->native->profile_value($user_id, 'country', (string) ($clinic['country'] ?? '')),
            'city' => $this->native->profile_value($user_id, 'city', (string) ($clinic['city'] ?? '')),
            'contacts' => $this->public_contacts($user_id, $clinic),
            'canonical_url' => $this->canonical_url($user),
        ];

        /** @var array<string, mixed> $profile */
        return apply_filters('sabri_public_experience/public_profile_data', $profile, $user);
    }

    public function canonical_url(WP_User $user, string $section = 'overview'): string
    {
        $profile_class = $this->visibility->profile_class($user);
        $base = match ($profile_class) {
            'founder' => home_url('/founder/'),
            'doctor' => home_url('/doctors/' . rawurlencode($user->user_nicename) . '/'),
            default => home_url('/profile/' . rawurlencode($user->user_nicename) . '/'),
        };

        return $section === 'overview' ? $base : trailingslashit($base . sanitize_key($section));
    }

    private function role_label(string $profile_class): string
    {
        return match ($profile_class) {
            'founder' => __('Founder — Official', 'sabri-public-experience'),
            'doctor' => __('Institution Verified Doctor', 'sabri-public-experience'),
            'teacher' => __('Teacher', 'sabri-public-experience'),
            'researcher' => __('Researcher', 'sabri-public-experience'),
            'student' => __('Student', 'sabri-public-experience'),
            default => __('Member', 'sabri-public-experience'),
        };
    }

    /** @param array<string,mixed> $clinic @return array<string, string> */
    private function public_contacts(int $user_id, array $clinic): array
    {
        $contacts = [];
        $values = [
            'phone' => (string) ($clinic['phone'] ?? $this->native->profile_value($user_id, 'phone')),
            'whatsapp' => (string) ($clinic['whatsapp'] ?? $this->native->profile_value($user_id, 'whatsapp')),
        ];

        foreach ($values as $field => $value) {
            if ($value !== '' && $this->visibility->can_show_contact($user_id, $field)) {
                $contacts[$field] = $value;
            }
        }

        return (array) apply_filters('sabri_public_experience/public_contacts', $contacts, $user_id);
    }
}
