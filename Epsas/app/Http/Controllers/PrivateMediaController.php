<?php

namespace App\Http\Controllers;

use App\Models\IncidenciaTecnica;
use App\Models\Lectura;
use App\Models\MedidorAnomalia;
use App\Models\Persona;
use App\Support\PrivateMedia;
use Symfony\Component\HttpFoundation\Response;

class PrivateMediaController extends Controller
{
    public function personaPhoto(Persona $persona): Response
    {
        abort_unless($persona->foto_path, 404);

        return PrivateMedia::response($persona->foto_path);
    }

    public function anomalyEvidence(MedidorAnomalia $anomalia): Response
    {
        abort_unless($anomalia->evidencia_path, 404);

        return PrivateMedia::response($anomalia->evidencia_path);
    }

    public function incidentEvidence(IncidenciaTecnica $incidencia): Response
    {
        abort_unless($incidencia->evidencia_path, 404);

        return PrivateMedia::response($incidencia->evidencia_path);
    }

    public function readingEvidence(Lectura $lectura): Response
    {
        abort_unless($lectura->evidencia_path, 404);

        return PrivateMedia::response($lectura->evidencia_path);
    }
}
