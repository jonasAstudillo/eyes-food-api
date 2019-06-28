<?php
define("UPLOAD_DIR", "img/uploads/");

class images{
    public static function get($urlSegments){
        
        // TODO: 2. Verificaciones, restricciones, defensas
        //?????????????????????????????????????????????????
        if (isset($urlSegments[1])) {
            throw new ApiException(
                400,
                0,
                "El recurso está mal referenciado",
                "http://localhost",
                "El recurso $_SERVER[REQUEST_URI] no esta sujeto a resultados"
            );
        }
        //barcode=urlSegments[0]
        if (isset($urlSegments[0])) {
            return self::retrieveImages($urlSegments[0]);
        }
        
    }
    
    public static function post($urlSegments) {
        //Si por ejemplo se manda solo users/ sin register o login
        if (!isset($urlSegments[1])) {
            throw new ApiException(
                400,
                0,
                "El recurso está mal referenciado",
                "http://localhost",
                "El recurso $_SERVER[REQUEST_URI] no esta sujeto a resultados"
            );
        }
        
        return self::uploadImage($urlSegments[0], $urlSegments[1], $urlSegments[2], $urlSegments[3]);
    }
    
    public static function put($urlSegments){
        
    }

    public static function delete($urlSegments){

    }

    public static function uploadImage($idUsuario, $codigoBarras, $tipoImagen, $alimento){
        $dir = UPLOAD_DIR;
        switch ($tipoImagen) {
            case "0":
                $dir = $dir.$alimento."/front/";
                break;
            case "1":
                $dir = $dir.$alimento."/ingredients/";
                break;
            case "2":
                $dir = $dir.$alimento."/nutrition/";
                break;
        }
        if (!empty($_FILES["myFile"])) {
        $myFile = $_FILES["myFile"];

        if ($myFile["error"] !== UPLOAD_ERR_OK) {
            echo "<p>An error occurred.</p>";
            exit;
        }

        // ensure a safe filename
        //$name = preg_replace("/[^A-Z0-9._-]/i", "_", $myFile["name"]);
        $parts = pathinfo($myFile["name"]);
        //Se guarda sin extensión para luego referenciarlo en el while
        $tempName = $codigoBarras;
        $name = $codigoBarras . "." . $parts["extension"];

        // don't overwrite an existing file
        $i = 0;
        while (file_exists($dir . $name)) {
            $i++;
            $name = $tempName . "(" . $i . ")" . "." . $parts["extension"];
        }

        // preserve file from temporary directory
        $success = move_uploaded_file($myFile["tmp_name"],
            $dir . $name);
        if (!$success) { 
            echo "Unable to save file.";
            exit;
        }
        else{
            return self::insertImage($codigoBarras,$tipoImagen, $alimento);
        }

        // set proper permissions on the new file
        chmod($dir . $name, 0644);
    }
        
    }
    
    public static function insertImage($codigoBarras,$tipoImagen, $alimento){
        try {
            $pdo = MysqlManager::get()->getDb();
            $estado = 1;
            $tabla;
            switch ($tipoImagen){
                case "0":
                    $estado = "alimentoFront";
                    break;
                case "1":
                    $estado = "alimentoIngr";
                    break;
                case "2":
                    $estado = "alimentoNutr";
                    break;
            }
            
            switch ($alimento){
                case "1":
                    $tabla = "alimentos";
                    break;
                case "2":
                    $tabla = "alimento_nuevo";
                    break;
            }

            $sentence = "UPDATE ".$tabla
               . " SET ".$estado." = '1' "
               . "WHERE codigoBarras = ?";

            $preparedStatement = $pdo->prepare($sentence);
            $preparedStatement->bindParam(1, $codigoBarras, PDO::PARAM_INT);
            
            if ($preparedStatement->execute()) {
                $rowCount = $preparedStatement->rowCount();
                //$dbResult = self::retrieveFoods($barcode);

//                if ($dbResult != NULL) {
//                    return $dbResult;
//                } else {
//                    throw new ApiException(
//                        400,
//                        0,
//                        "Número de identificación o contraseña inválidos",
//                        "http://localhost",
//                        "Puede que no exista un usuario creado con el correo \"$userId\" o que la contraseña \"$password\" sea incorrecta."
//                    );
//                }
            }
        }catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al intentar insertar el usuario: " . $e->getMessage());
        }        

    }
    
    private static function findImage($name) {
        try {
            $pdo = MysqlManager::get()->getDb();

            // Componer sentencia SELECT
            $sentence = "SELECT * FROM fotos_alimentos WHERE ruta=?";

            // Preparar sentencia
            $preparedSentence = $pdo->prepare($sentence);
            $preparedSentence->bindParam(1, $name, PDO::PARAM_INT);

            // Ejecutar sentencia
            if ($preparedSentence->execute()) {
                $photoData = $preparedSentence->fetch(PDO::FETCH_ASSOC);

                return $photoData;

            } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos. Detalles:" . $pdo->errorInfo()[2]
                );
            }

        } catch (PDOException $e) {
            throw new ApiException(
                500,
                0,
                "Error de base de datos en el servidor",
                "http://localhost",
                "Ocurrió el siguiente error al consultar el usuario: " . $e->getMessage());
        }
    }
    
    private static function retrieveImages($codigoBarras)
    {
        try {
            $pdo = MysqlManager::get()->getDb();

            $comando = "SELECT * "
                    . "FROM fotos_alimentos "
                    . "WHERE codigoBarras = ? AND autorizacion = '1'";

                // Preparar sentencia
                $sentencia = $pdo->prepare($comando);
                $sentencia->bindParam(1, $codigoBarras, PDO::PARAM_INT);

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                return $sentencia->fetchAll(PDO::FETCH_ASSOC);
            } else {
                throw new ApiException(
                    500,
                    0,
                    "Error de base de datos en el servidor",
                    "http://localhost",
                    "Hubo un error ejecutando una sentencia SQL en la base de datos. Detalles:" . $pdo->errorInfo()[2]
                );
            }

        } catch (PDOException $e) {
        throw new ApiException(
            500,
            0,
            "Error de base de datos en el servidor",
            "http://localhost",
            "Ocurrió el siguiente error al consultar las citas médicas: " . $e->getMessage());
        }
    }
}