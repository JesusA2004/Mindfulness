<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactoRecibido;

class ContactoController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'  => ['required','string','min:3','max:100'],
            'email'   => ['required','email','max:120'],
            'asunto'  => ['required','string','min:4','max:150'],
            'mensaje' => ['required','string','min:10'],
        ]);

        // Datos contextuales
        $meta = [
            'ip' => $request->ip(),
            'ua' => substr($request->userAgent() ?? '', 0, 255),
            'when' => now()->format('Y-m-d H:i:s'),
        ];

        // A quién llega (puedes moverlo a .env CONTACT_TO)
        $to = env('CONTACT_TO', 'jesusarizmendimaya@gmail.com');

        Mail::to($to)->send(new ContactoRecibido($data, $meta));

        return response()->json(['ok' => true, 'message' => 'Mensaje enviado'], 200);
    }
}
