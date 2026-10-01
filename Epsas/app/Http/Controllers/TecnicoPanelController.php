<?php

namespace App\Http\Controllers;

use App\Models\OrdenTecnica;
use App\Services\TechnicalPanelService;
use Illuminate\Http\Request;

class TecnicoPanelController extends Controller
{
    public function __construct(private TechnicalPanelService $technical)
    {
    }

    public function consumo()
    {
        if (auth()->user()?->hasRole('administrador')) {
            return redirect()->to(route('tecnico.lecturas.create', [], false));
        }

        return redirect()->route('tecnico.consumo.index');
    }

    public function consumoTecnico()
    {
        return $this->technical->consumo();
    }
    public function anomalias() { return $this->technical->anomalias(); }
    public function storeAnomalia(Request $request) { return $this->technical->storeAnomalia($request); }
    public function cortes() { return $this->technical->cortes(); }
    public function storeCorte(Request $request) { return $this->technical->storeCorte($request); }
    public function reconexiones() { return $this->technical->reconexiones(); }
    public function storeReconexion(Request $request) { return $this->technical->storeReconexion($request); }
    public function instalaciones() { return $this->technical->instalaciones(); }
    public function storeInstalacion(Request $request) { return $this->technical->storeInstalacion($request); }
    public function mantenimiento() { return $this->technical->mantenimiento(); }
    public function storeMantenimiento(Request $request) { return $this->technical->storeMantenimiento($request); }
    public function operacion() { return $this->technical->operacion(); }
    public function storeOperacion(Request $request) { return $this->technical->storeOperacion($request); }
    public function reportes() { return $this->technical->reportes(); }
    public function storeReporte(Request $request) { return $this->technical->storeReporte($request); }
    public function incidencias() { return $this->technical->incidencias(); }
    public function storeIncidencia(Request $request) { return $this->technical->storeIncidencia($request); }
    public function medidorCatalog(Request $request) { return $this->technical->medidorCatalog($request); }
    public function socioCatalogSearch(Request $request) { return $this->technical->socioCatalogSearch($request); }
    public function approveReconexion(OrdenTecnica $orden) { return $this->technical->approveReconexion($orden); }
    public function warmIndexCache(): void { $this->technical->warmIndexCache(); }
}
