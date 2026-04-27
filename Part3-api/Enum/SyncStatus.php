<?php

enum SyncStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed  = 'failed';
}
