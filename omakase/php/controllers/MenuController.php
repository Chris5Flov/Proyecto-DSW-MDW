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
        [$nombre, $categoria, $precioBase, $tasaImpuesto] = $this->validarCamposPlato($datos);
        Respuesta::creado($this->menu->registrarPlato($id, $nombre, $categoria, $precioBase, $tasaImpuesto));
    }

    public function actualizarPlato(string $platoId): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        $id = Validacion::textoObligatorio(urldecode($platoId), 'El id del plato es obligatorio');
        $datos = Validacion::leerJson();
        [$nombre, $categoria, $precioBase, $tasaImpuesto] = $this->validarCamposPlato($datos);
        Respuesta::ok($this->menu->actualizarPlato($id, $nombre, $categoria, $precioBase, $tasaImpuesto));
    }

    public function subirImagenPlato(string $platoId): never
    {
        Sesion::exigirRol($this->usuarios, ['ADMINISTRADOR']);
        $id = Validacion::textoObligatorio(urldecode($platoId), 'El id del plato es obligatorio');

        $archivo = $_FILES['imagen'] ?? null;
        if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ReglaNegocioException('Debes adjuntar una imagen válida');
        }

        $maxBytes = 5 * 1024 * 1024; // 5 MB
        if ((int) ($archivo['size'] ?? 0) <= 0 || (int) $archivo['size'] > $maxBytes) {
            throw new ReglaNegocioException('La imagen no puede superar los 5 MB');
        }

        $tmpPath = (string) ($archivo['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new ReglaNegocioException('Archivo subido no válido');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath) ?: '';
        $extensionesPermitidas = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($extensionesPermitidas[$mime])) {
            throw new ReglaNegocioException('Formato de imagen no permitido (usa JPG, PNG o WEBP)');
        }

        // Asegura que el plato exista antes de guardar el archivo
        $this->menu->actualizarImagenPlato($id, null);

        $nombreSeguro = preg_replace('/[^A-Za-z0-9_-]/', '_', $id);
        $ext = $extensionesPermitidas[$mime];
        $rutaRelativa = "img/platos/{$nombreSeguro}.{$ext}";

        // Ajusta la ruta física según dónde viva tu carpeta public/ respecto a php/controllers/
        $directorioDestino = __DIR__ . '/../../public/img/platos';
        if (!is_dir($directorioDestino) && !mkdir($directorioDestino, 0775, true) && !is_dir($directorioDestino)) {
            throw new ReglaNegocioException('No se pudo crear el directorio de imágenes');
        }

        $destinoFinal = $directorioDestino . '/' . $nombreSeguro . '.' . $ext;
        if (!move_uploaded_file($tmpPath, $destinoFinal)) {
            throw new ReglaNegocioException('No se pudo guardar la imagen en el servidor');
        }

        $platoActualizado = $this->menu->actualizarImagenPlato($id, $rutaRelativa);
        Respuesta::ok($platoActualizado);
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

/** @return array{0:string, 1:string, 2:string, 3:string} */
    private function validarCamposPlato(array $datos): array
    {
        $nombre = Validacion::textoObligatorio($datos['nombre'] ?? null, 'El nombre del plato es obligatorio');
        $categoria = Validacion::textoObligatorio($datos['categoria'] ?? null, 'La categoría del plato es obligatoria');
        if (mb_strlen($categoria) > 60) {
            throw new ReglaNegocioException('La categoría no puede exceder 60 caracteres');
        }

        $precio = Validacion::decimal($datos['precioBase'] ?? null, 'precioBase', true);
        if (bccomp($precio, '0', 6) <= 0) {
            throw new ReglaNegocioException('El precio debe ser mayor a cero');
        }
        Validacion::maximoDosDecimales($precio, 'El precio no puede tener más de dos decimales');

        $tasa = Validacion::decimal($datos['tasaImpuesto'] ?? null, 'tasaImpuesto', true);
        if (bccomp($tasa, '0', 6) < 0 || bccomp($tasa, '1', 6) > 0) {
            throw new ReglaNegocioException('La tasa de impuesto debe estar entre 0 y 1 (por ejemplo 0.16)');
        }

        return [$nombre, $categoria, Validacion::dinero($precio), $tasa];
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
