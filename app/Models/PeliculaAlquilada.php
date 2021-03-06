<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeliculaAlquilada extends Model
{
    use HasFactory;

    protected $table = "peliculas_alquiladas";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_pelicula',
        'id_user',
        'devuelta',
        'fecha_alquiler',
        'fecha_devolucion'
    ];

    // Uso la instancia de Carbon para el manejo de fechas
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'fecha_alquiler',
        'fecha_devolucion'
    ];

    // Una película alquilada pertenece a una película
    public function pelicula()
    {
        return $this->belongsTo(Pelicula::class, 'id_pelicula');
    }

    // Una película alquilada pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
