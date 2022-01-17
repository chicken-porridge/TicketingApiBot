<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportPhoto extends Model
{
    protected $table = 'report_photos';
    protected $fillable = ['report_id','photos'];
}
