<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = $request->user()->notifications()
            ->latest()
            ->paginate(15);

        $sinLeer = $request->user()->unreadNotifications()->count();

        return view('notificaciones.index', compact('notificaciones', 'sinLeer'));
    }

    public function marcarLeidas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Notificaciones marcadas como leídas.');
    }

    public function marcarLeida(Request $request, DatabaseNotification $notificacion)
    {
        abort_unless(
            (string) $notificacion->notifiable_id === (string) $request->user()->getAuthIdentifier()
                && $notificacion->notifiable_type === $request->user()->getMorphClass(),
            403
        );

        $notificacion->markAsRead();

        return back()->with('status', 'Notificación marcada como leída.');
    }
}
