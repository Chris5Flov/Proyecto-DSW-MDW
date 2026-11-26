<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/ReglaNegocioException.php';
require_once __DIR__ . '/../core/Respuesta.php';
require_once __DIR__ . '/../core/Sesion.php';
require_once __DIR__ . '/../core/Validacion.php';
require_once __DIR__ . '/../repositories/MenuRepository.php';
require_once __DIR__ . '/../repositories/UsuarioRepository.php';

/**
 * RF1 (Administrador): CRUD de platos y modificadores del menú.
 * Mesero tiene acceso de lectura para poder levantar órdenes.
 */
final class MenuController
{
    private MenuRepository $menu;
    private UsuarioRepository $usuarios;

    public function __construct(MenuRepository $menu, UsuarioRepository $usuarios)
    {
        $this->menu = $menu;
        $this->usuarios = $usuarios;
    }

    public function listarPlatos(): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR', 'MESERO']);
        Respuesta::ok($this->menu->listarPlatos());
    }

    public function registrarPlato(): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        $datos = Validacion::leerJson();
        $id = Validacion::textoObligatorio($datos['id'] ?? null, 'El id del plato es obligatorio');
        if (mb_strlen($id) > 32) {
            throw new ReglaNegocioException('El id del plato no puede exceder 32 caracteres');
        }
        [$nombre, $precioBase, $tasaImpuesto] = $this->validarCamposPlato($datos);
        Respuesta::creado($this->menu->registrarPlato($id, $nombre, $precioBase, $tasaImpuesto));
    }

    public function actualizarPlato(string $platoId): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        $id = Validacion::textoObligatorio(urldecode($platoId), 'El id del plato es obligatorio');
        $datos = Validacion::leerJson();
        [$nombre, $precioBase, $tasaImpuesto] = $this->validarCamposPlato($datos);
        Respuesta::ok($this->menu->actualizarPlato($id, $nombre, $precioBase, $tasaImpuesto));
    }

    public function eliminarPlato(string $platoId): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        $id = Validacion::textoObligatorio(urldecode($platoId), 'El id del plato es obligatorio');
        $this->menu->eliminarPlato($id);
        Respuesta::sinContenido();
    }

    public function listarModificadores(): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR', 'MESERO']);
        Respuesta::ok($this->menu->listarModificadores());
    }

    public function registrarModificador(): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        $datos = Validacion::leerJson();
        $id = Validacion::textoObligatorio($datos['id'] ?? null, 'El id del modificador es obligatorio');
        if (mb_strlen($id) > 32) {
            throw new ReglaNegocioException('El id del modificador no puede exceder 32 caracteres');
        }
        [$nombre, $tipo, $costoExtra] = $this->validarCamposModificador($datos);
        Respuesta::creado($this->menu->registrarModificador($id, $nombre, $tipo, $costoExtra));
    }

    public function actualizarModificador(string $modificadorId): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        $id = Validacion::textoObligatorio(urldecode($modificadorId), 'El id del modificador es obligatorio');
        $datos = Validacion::leerJson();
        [$nombre, $tipo, $costoExtra] = $this->validarCamposModificador($datos);
        Respuesta::ok($this->menu->actualizarModificador($id, $nombre, $tipo, $costoExtra));
    }

    public function eliminarModificador(string $modificadorId): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        $id = Validacion::textoObligatorio(urldecode($modificadorId), 'El id del modificador es obligatorio');
        $this->menu->eliminarModificador($id);
        Respuesta::sinContenido();
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function validarCamposPlato(array $datos): array
    {
        $nombre = Validacion::textoObligatorio($datos['nombre'] ?? null, 'El nombre del plato es obligatorio');
        $precio = Validacion::decimal($datos['precioBase'] ?? null, 'precioBase', true);
        if (bccomp($precio, '0', 6) <= 0) {
            throw new ReglaNegocioException('El precio debe ser mayor a cero');
        }
        Validacion::maximoDosDecimales($precio, 'El precio no puede tener más de dos decimales');

        $tasa = Validacion::decimal($datos['tasaImpuesto'] ?? null, 'tasaImpuesto', true);
        if (bccomp($tasa, '0', 6) < 0 || bccomp($tasa, '1', 6) > 0) {
            throw new ReglaNegocioException('La tasa de impuesto debe estar entre 0 y 1 (por ejemplo 0.16)');
        }

        return [$nombre, Validacion::dinero($precio), $tasa];
    }

    /** @return array{0:string, 1:string, 2:string} */
    private function validarCamposModificador(array $datos): array
    {
        $nombre = Validacion::textoObligatorio($datos['nombre'] ?? null, 'El nombre del modificador es obligatorio');
        $tipo = Validacion::enumeracion($datos['tipo'] ?? null, ['EXTRA', 'EXCLUSION'], 'tipo');
        $costo = Validacion::decimal($datos['costoExtra'] ?? null, 'costoExtra', true);
        if (bccomp($costo, '0', 6) < 0) {
            throw new ReglaNegocioException('El costo extra no puede ser negativo');
        }
        if ($tipo === 'EXCLUSION' && bccomp($costo, '0', 6) !== 0) {
            throw new ReglaNegocioException('Una exclusión no puede tener costo extra');
        }
        Validacion::maximoDosDecimales($costo, 'El costo extra no puede tener más de dos decimales');

        return [$nombre, $tipo, Validacion::dinero($costo)];
    }
}
