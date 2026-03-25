<?php

namespace App\Enums;

use Illuminate\Support\Facades\Request;

enum NavbarLink: string
{
    case FEATURES = '#features';
        // case DOWNLOAD = '#download';
    case ABOUT = '#about';
    case CONTACT = '#contact';
    case PRICING = '#pricing';
    case BLOGS = '/blogs';
    case VENDOR_REGISTER = '/vendor/register';
    case SOCIAL_MEDIA = '/social-plans';

    public function label(): string
    {
        return match ($this) {
            self::FEATURES => 'Features',
            // self::DOWNLOAD => 'Download',
            self::ABOUT => 'About',
            self::CONTACT => 'Contact',
            self::PRICING => 'Pricing',
            self::BLOGS => 'Blogs',
            self::VENDOR_REGISTER => 'Vendor Register',
            self::SOCIAL_MEDIA => 'Social Plans',
        };
    }

    public function url(): string
    {
        // Hash-based sections should always point to homepage
        if (str_starts_with($this->value, '#')) {
            return url('/') . $this->value;
        }

        return url($this->value);
    }

    /**
     * Detect active link
     */
    public function isActive(): bool
    {
        // Blogs page
        if ($this === self::BLOGS) {
            return Request::is('blogs*');
        }

        // Vendor register page
        if ($this === self::VENDOR_REGISTER) {
            return Request::is('vendor/register');
        }

        // Vendor register page
        if ($this === self::SOCIAL_MEDIA) {
            return Request::is('social-plans');
        }

        // Home page hash sections
        if (str_starts_with($this->value, '#')) {
            return Request::is('/') && request()->getRequestUri() === '/' . $this->value;
        }

        return false;
    }
}
