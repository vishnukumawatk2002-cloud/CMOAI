<?php

namespace App\Domain\Enums;

enum SocialPlatform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case LinkedIn = 'linkedin';
    case X = 'x';
    case YouTube = 'youtube';
    case Snapchat = 'snapchat';
    case Pinterest = 'pinterest';
    case Threads = 'threads';
    case GoogleBusiness = 'google_business';
    case Multi = 'multi';
}
