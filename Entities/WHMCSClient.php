<?php

namespace Modules\LJPcWHMCSModule\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WHMCSClient
 *
 * @package Modules\LJPcWHMCSModule\Entities
 *
 * This model is never saved to the database and is only used to define the structure of the WHMCS client data.
 */
class WHMCSClient extends Model {

    protected $fillable = [
        'id',
        'firstname',
        'lastname',
        'companyname',
        'email',
        'datecreated',
        'groupid',
        'status',
    ];
}
