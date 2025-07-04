<?php

namespace App;

enum EmailStatus: string
{
    case Sent = "sent";
    case Draft = "draft";
}
