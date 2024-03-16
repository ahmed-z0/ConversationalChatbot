<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assistants extends Model
{
    protected $fillable = ['name','assistant_id'];
    use HasFactory;
    protected $table = 'assistants';
    protected $primaryKey = "id";
}
