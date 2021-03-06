<?php

namespace App\Http\Controllers;

use App\Mail\AlquiladaMailable;
use App\Models\Pelicula;
use App\Models\PeliculaAlquilada;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PeliculaAlquiladaController extends Controller
{
    public function __construct()
    {
        // En caso de entrar a alquilar una película y no estar logeado. 
        // Te redirige al login, inicias sesión y vuelve a la vista de alquiler
        $this->middleware('auth');
    }

    /**
    * Sólo creo los métodos para visualizar las películas alquiladas. Para añadirla y 
    * para borrarla
    */ 
    public function index()
    {
        $usuario = Auth::User();

        if ($usuario->id == 1)
        {
            return redirect()->back()->withErrors('Los administradores no pueden alquilar películas');
        } else
        {
            $peliculasAlquiladas = PeliculaAlquilada::where('id_user', $usuario->id)->where('devuelta', 0)->get();

            return view('peliculas-alquiladas.index', compact('peliculasAlquiladas', 'usuario'));
        }
    }

    // Muestro las películas que ya ha devuelto el usuario
    public function historial()
    {
        $usuario = Auth::user();
        $historial = PeliculaAlquilada::where('id_user', $usuario->id)->where('devuelta', 1)->get();

        return view('peliculas-alquiladas.historial', compact('historial'));
    }

    public function create($PeliculaAlquilada)
    {
        /**
         * Si existe el id de la película que se quiere alquilar se llama al método store
         * desde el formulario de confirmación de la vista create. Store se encarga de 
         * insertar la película alquilada en la base de datos
         */
        $usuario = Auth::user()->id;
        $numPeliculasAlquiladas = PeliculaAlquilada::where('id_user', $usuario)->where('devuelta', 0)->count();

        // Si no existe la película por el id manda un error 404
        $PeliculaAlquilada = Pelicula::findOrFail($PeliculaAlquilada);

        if ($usuario == 1)
        {
            return redirect()->route('peliculas.show', ['pelicula' => $PeliculaAlquilada->id])->withErrors('Los administradores no pueden alquilar películas');
        } else if ($numPeliculasAlquiladas >= 6)
        {
            return redirect()->route('peliculas.show', ['pelicula' => $PeliculaAlquilada->id])->withErrors('Has alcanzado el límite de alquiler de películas');
        } else if ($PeliculaAlquilada->cantidad <= 0)
        {
            return redirect()->route('peliculas.show', ['pelicula' => $PeliculaAlquilada->id])->withErrors('Lo sentimos, no tenemos stock de esta película');
        } else
        {
            return view('peliculas-alquiladas.create', compact('PeliculaAlquilada'));
        }        
    }

    public function store($PeliculaAlquilada)
    {
        /**
         * Saco un error flash de sesión si el usuario quiere alquilar una película
         * que ya tenga alquilada
         */
        $idUsuario = auth()->user()->id;
        $comprobarAlquiler = PeliculaAlquilada::where('id_pelicula', $PeliculaAlquilada)->where('id_user', $idUsuario)->where('devuelta', false)->count();

        if ($comprobarAlquiler > 0)
        {
            return redirect()->back()
                    ->withInput(request()->all())
                    ->withErrors('Ya tienes alquilada esta película');
        } else {
            /**
             * Si no está alquilada la inserto en la BD y creo un mensaje de éxito
             */
            $PeliculaAlquilada = Pelicula::findOrFail($PeliculaAlquilada);
            $fechaAlquiler = Carbon::now();

            PeliculaAlquilada::create([
                'id_pelicula' => $PeliculaAlquilada->id,
                'id_user' => $idUsuario,
                'fecha_alquiler' => $fechaAlquiler
            ]);

            // Dismiuyo la cantidad de la película
            $peliculaCreada = PeliculaAlquilada::orderBy('id', 'DESC')->first();
            $pelicula = Pelicula::where('id', $peliculaCreada->id_pelicula)->first();
            $cantidad = $pelicula->cantidad;

            Pelicula::where('id', $peliculaCreada->id_pelicula)->update([
                "cantidad" => $cantidad - 1
            ]);

            // Mando un correo al usuario
            $correo = new AlquiladaMailable;
            Mail::to(auth()->user()->email)->send($correo);

            return redirect()
                    ->route('peliculas.show', ['pelicula' => $PeliculaAlquilada->id])
                    ->withSuccess('Has alquilado con éxito la película');
        }
    }

    // Proceso para devolver la película
    public function edit(PeliculaAlquilada $PeliculaAlquilada)
    {
        // Muestro un formulario de confirmación (Controlar que el usuario la tenga alquilada)
        return view('peliculas-alquiladas.edit', compact('PeliculaAlquilada'));
    }

    public function update(PeliculaAlquilada $PeliculaAlquilada)
    {
        $fechaDevolucion = Carbon::now();

        // Cambio el valor del booleano devuelto a true
        $PeliculaAlquilada->update([
            'devuelta' => 1,
            'fecha_devolucion' => $fechaDevolucion
        ]);

        // Aumento la cantidad de la película
        $pelicula = Pelicula::where('id', $PeliculaAlquilada->id_pelicula)->first();
        $cantidad = $pelicula->cantidad;

        Pelicula::where('id', $PeliculaAlquilada->id_pelicula)->update([
            "cantidad" => $cantidad + 1
        ]);

        return redirect()
                    ->route('peliculas-alquiladas.index')
                    ->withSuccess('Has devuelto con éxito la película');
    }
}
