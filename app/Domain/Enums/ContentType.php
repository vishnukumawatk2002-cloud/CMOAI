<?php

namespace App\Domain\Enums;

enum ContentType: string
{
    case Post = 'post';
    case Carousel = 'carousel';
    case ReelScript = 'reel_script';
    case ImageCaption = 'image_caption';
    case Hashtags = 'hashtags';
    case ThirtyDayPlan = 'thirty_day_plan';
    case Thread = 'thread';
}
