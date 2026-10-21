<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    public const ADMINISTRADOR = 'administrador';
    public const CAPTURISTA    = 'capturista';

    protected $table = 'roles';
    protected $fillable = ['nombre'];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }
}