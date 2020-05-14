<?php
/**
 * * * COMPARA * * *
 * 
 * Comparación de estructuras de dos bases de datos (MySQL)
 * 
 * @author Leonardo González
 */

define("HOST",      "localhost"); 
define("USER",      "leonardo"); 
define("PASSWORD",  "leo8721208");
define("DATABASE1", "devel");
define("DATABASE2", "vigilantia2");

/**
 * Primera base de datos a consultar
 */
$conexion_1 = new mysqli(
    HOST,
    USER,
    PASSWORD,
    DATABASE1
);

/**
 * Segunda base de datos a consultar
 */
$conexion_2 = new mysqli(
    HOST,
    USER,
    PASSWORD,
    DATABASE2
);


/**
 * Comparación de bases de datos propuestas
 * 
 * @param array $conexiones - Conexiones con las bases de datos
 * @return void
 */
function compararBasesDeDatos(array $conexiones) : void {

    $DB_1 = DATABASE1;
    $DB_2 = DATABASE2;

    $tablasDelOrigen_1 = obtenerTablasDelOrigen($conexiones[0]);
    $tablasDelOrigen_2 = obtenerTablasDelOrigen($conexiones[1]);

    // Tablas del origen 1 faltantes en el 2
    $tablasFaltantesEn2 = array_diff($tablasDelOrigen_1, $tablasDelOrigen_2);
    mostrarTablasFaltantes($DB_2, $tablasFaltantesEn2);

    // Tablas del origen 2 faltantes en el 1
    $tablasFaltantesEn1 = array_diff($tablasDelOrigen_2, $tablasDelOrigen_1);
    mostrarTablasFaltantes($DB_1, $tablasFaltantesEn1);

    $tablasExistentesEnAmbos = array_intersect($tablasDelOrigen_1, $tablasDelOrigen_2);

    foreach ($tablasExistentesEnAmbos as $tabla) {

        $camposDeLaTabla_1 = obtenerCamposDeLaTabla($conexiones[0], $tabla);
        $camposDeLaTabla_2 = obtenerCamposDeLaTabla($conexiones[1], $tabla);

        // Campos de la tabla del origen 1 faltantes en el 2
        $camposFaltantesEn2 = array_diff($camposDeLaTabla_1, $camposDeLaTabla_2);
        mostrarCamposFaltantes([
            "nombreBaseDeDatos" => $DB_2,
            "nombreTabla"       => $tabla
        ], $camposFaltantesEn2);
        agregarCamposFaltantes([
            "conexiones"        => $conexiones,
            "nombreTabla"       => $tabla,
            "camposFaltantes"   => $camposFaltantesEn2
        ]);

        // Campos de la tabla del origen 2 faltantes en el 1
        $camposFaltantesEn1 = array_diff($camposDeLaTabla_2, $camposDeLaTabla_1);
        mostrarCamposFaltantes([
            "nombreBaseDeDatos" => $DB_1,
            "nombreTabla"       => $tabla
        ], $camposFaltantesEn1);

        $camposExistentesEnAmbos = array_intersect($camposDeLaTabla_1, $camposDeLaTabla_2);

        foreach ($camposExistentesEnAmbos as $campo) {

            $detallesDelCamposDeLaTabla_1 = obtenerDetallesDelCampo($conexiones[0], $tabla, $campo);
            $detallesDelCamposDeLaTabla_2 = obtenerDetallesDelCampo($conexiones[1], $tabla, $campo);

            // Detalles del campo del origen 1 faltantes en el 2
            $detallesFaltantesEn2 = array_diff($detallesDelCamposDeLaTabla_1, $detallesDelCamposDeLaTabla_2);
            mostrarDetallesFaltantes([
                "nombreBaseDeDatos" => $DB_2,
                "nombreTabla"       => $tabla,
                "nombreCampo"       => $campo
            ], $detallesFaltantesEn2);

            // Modificar detalles faltantes del campo 1
            if(count($detallesFaltantesEn2) > 0):
                ajustarDetallesDelCampo([
                    "conexion"      => $conexiones[1],
                    "nombreTabla"   => $tabla,
                    "detallesCampo" => $detallesDelCamposDeLaTabla_1
                ]);
            endif;
        }
    }
}

/**
 * Obtener tablas de la base de datos
 * 
 * @param object $conexion - Conexión con la base de datos
 * @return array $tablas
 */
function obtenerTablasDelOrigen(object $conexion) : array {

    $tablas = array();

    $resultado = $conexion->query('SHOW TABLES');

    while ($fila = $resultado->fetch_row()) {
        array_push($tablas, $fila[0]);
    }

    return $tablas;
}

/**
 * Mostrar tablas faltantes en los origenes
 * 
 * @param string $nombreBaseDeDatos
 * @param array $tablasFaltantes
 * @return void
 */
function mostrarTablasFaltantes(string $nombreBaseDeDatos, array $tablasFaltantes) : void {

    if (count($tablasFaltantes) > 0):

        echo "Tablas faltantes en la base de datos: " . $nombreBaseDeDatos . "\n";

        foreach ($tablasFaltantes as $tabla) {
            echo "- " . $tabla . "\n";
        }

        echo "\n";

    endif;
}

/**
 * Obtener campos de la tabla
 * 
 * @param object $conexion - Conexión con la base de datos
 * @param string $nombreTabla - Nombre de la tabla a consultar
 * @return array $campos
 */
function obtenerCamposDeLaTabla(object $conexion, string $nombreTabla) : array {

    $campos = array();

    $resultado = $conexion->query('DESCRIBE ' . $nombreTabla);

    while ($fila = $resultado->fetch_row()) {
        array_push($campos, $fila[0]);
    }

    return $campos;
}

/**
 * Mostrar campos faltantes en las tablas
 * 
 * @param array $informacionDelOrigen ["$nombreBaseDeDatos" => string, "nombreTabla" => string]
 * @param array $camposFaltantes
 * @return void
 */
function mostrarCamposFaltantes(array $informacionDelOrigen, array $camposFaltantes) : void {

    if (count($camposFaltantes) > 0):

        echo "Campos faltantes en la tabla: " . 
            $informacionDelOrigen["nombreBaseDeDatos"] . 
            "." .
            $informacionDelOrigen["nombreTabla"] . 
            "\n";

        foreach ($camposFaltantes as $campos) {
            echo "- " . $campos . "\n";
        }

        echo "\n";
        
    endif;
}

/**
 * Agregar campos faltantes en la tabla
 * 
 * @param  array [conexiones => array, nombreTabla => string, camposFaltantes => array
 * @return void
 */
function agregarCamposFaltantes($informacion) {
    foreach ($informacion['camposFaltantes'] as $campo) {
        var_dump($informacion['camposFaltantes']);
        agregarCampoFaltante([
            'conexion'      => $informacion['conexiones'][1],
            'nombreTabla'   => $informacion['nombreTabla'],
            'detallesCampo' => obtenerDetallesDelCampo($informacion['conexiones'][0], $informacion['nombreTabla'], $campo)
        ]);
    }
}

/**
 * Agregar campo faltante
 * 
 * @param array $informacion [conexion => object, nombreTabla => string, detallesCampo => array]
 * @return void
 */
function agregarCampoFaltante($informacion) {

    // Establecer estructura del nuevo campo
    $detalles = ($informacion['detallesCampo'][0]).' '.
                ($informacion['detallesCampo'][1]).' '.
                ($informacion['detallesCampo'][2] == 'YES' ? 'NULL' : 'NOT NULL');

    // Construccion de la consulta
    $consulta = 'ALTER TABLE '.$informacion['nombreTabla'].' ADD COLUMN '.$detalles;

    // Ejecutar consulta de adicion del campo
    $informacion['conexion']->query($consulta);

    // Colocar clave primaria
    if ($informacion['detallesCampo'][3] == 'PRI'):
        $informacion['conexion']->query('ALTER TABLE '.$informacion['nombreTabla'].' ADD PRIMARY KEY('.$informacion['detallesCampo'][0].')');
    endif;
}

/**
 * Obtener detalles del campo
 * 
 * @param object $conexion - Conexión con la base de datos
 * @param string $nombreTabla - Nombre de la tabla a consultar
 * @param string $nombreCampo - Nombre del campo a consultar
 * @return array $detalles
 */
function obtenerDetallesDelCampo(object $conexion, string $nombreTabla, string $nombreCampo) : array {

    $detalles = array();

    $resultado = $conexion->query('DESCRIBE ' . $nombreTabla);

    while ($fila = $resultado->fetch_row()) {

        if ($fila[0] === $nombreCampo):

            $detalles = [
                $fila[0],
                $fila[1],
                $fila[2],
                $fila[3],
                $fila[4],
                $fila[5]
            ];
        endif;
    }

    return $detalles;
}

/**
 * Mostrar detalles del campo faltantes en la otra tabla
 * 
 * @param array $informacionDelOrigen ["$nombreBaseDeDatos" => string, "nombreTabla" => string, "nombreCampo" => string]
 * @param array $detallesFaltantes
 * @return void
 */
function mostrarDetallesFaltantes(array $informacionDelOrigen, array $detallesFaltantes) : void {

    if (count($detallesFaltantes) > 0):

        echo "Estructuras faltantes en el campo: " . 
            $informacionDelOrigen["nombreBaseDeDatos"] . 
            "." .
            $informacionDelOrigen["nombreTabla"] . 
            "." .
            $informacionDelOrigen["nombreCampo"] . 
            "\n";

        foreach ($detallesFaltantes as $detalle) {
            echo "- " . $detalle . "\n";
        }

        echo "\n";
        
    endif;
}

/**
 * Ajustar campo con los detalles faltantes
 * 
 * @param array $informacion [conexion => object, nombreTabla => string, nombreCampo => string, detallesCampo => array]
 * @return void
 */
function ajustarDetallesDelCampo($informacion) {

    // Establecer nuevos detalles del campo
    $detalles = ($informacion['detallesCampo'][0]).' '.
                ($informacion['detallesCampo'][1]).' '.
                ($informacion['detallesCampo'][2] == 'YES' ? 'NULL' : 'NOT NULL').' '.
                ($informacion['detallesCampo'][5] == 'auto_increment' ? 'AUTO_INCREMENT' : '');

    // Construccion de la consulta
    $consulta = 'ALTER TABLE '.$informacion['nombreTabla'].' MODIFY COLUMN '.$detalles;

    // Ejecutar consultar de modificacion del campo
    $informacion['conexion']->query($consulta);
}

echo "Comparación de Bases de datos: " . DATABASE1 . " y " . DATABASE2 . " \n";
echo "\n";

compararBasesDeDatos([
    $conexion_1,
    $conexion_2
]);
?>
