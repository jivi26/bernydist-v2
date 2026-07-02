<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /**
     * Recibe email o clave_cliente, busca la dirección principal del cliente
     * y envía un correo con instrucciones para recuperar la contraseña.
     * El ERP es dueño de la contraseña — no se genera token ni se cambia nada aquí;
     * solo se notifica al equipo para que restablezcan manualmente (flujo legacy).
     */
    public function send(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'min:3'],
        ], [
            'email.required' => 'Debes ingresar tu correo o clave de cliente.',
            'email.min'      => 'El valor ingresado es demasiado corto.',
        ]);

        $identifier = trim($request->input('email'));

        // Buscar por email (tabla _BR_EMAILS_CLIENTES) o por CLAVE_CLIENTE (_CLIENTES)
        $row = DB::selectOne(
            "SELECT c.CLAVE_CLIENTE, c.NOMBRE, d.EMAIL
             FROM _CLIENTES c
             INNER JOIN _DIRS_CLIENTES d ON d.CLIENTE_ID = c.CLIENTE_ID AND d.ES_DIR_PPAL = 'S'
             LEFT JOIN _BR_EMAILS_CLIENTES e ON e.CLIENTE_ID = c.CLIENTE_ID
             WHERE e.EMAIL = ? OR c.CLAVE_CLIENTE = ?
             LIMIT 1",
            [$identifier, $identifier]
        );

        // Respuesta genérica siempre para no revelar si el usuario existe
        $message = 'Si encontramos una cuenta con esa información, recibirás instrucciones en tu correo registrado.';

        if ($row && !empty($row->EMAIL)) {
            // En Fase 5 se implementará envío real de email con token.
            // Por ahora notificamos al equipo interno para restablecer manualmente.
            Log::info('Solicitud recuperación contraseña', [
                'clave'  => $row->CLAVE_CLIENTE,
                'nombre' => $row->NOMBRE,
                'email'  => $row->EMAIL,
            ]);

            try {
                Mail::raw(
                    "Cliente {$row->NOMBRE} ({$row->CLAVE_CLIENTE}) solicitó recuperar su contraseña.\n"
                    . "Email registrado: {$row->EMAIL}",
                    function ($mail) use ($row) {
                        $mail->to(config('berny.emails.ppal'))
                             ->subject("Recuperación de contraseña — {$row->CLAVE_CLIENTE}");
                    }
                );
            } catch (\Throwable $e) {
                Log::error('Error enviando correo recuperación: ' . $e->getMessage());
            }
        }

        return back()->with('success', $message);
    }
}
