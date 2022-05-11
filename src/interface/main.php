<?php
session_start();
// Connection à la base de données
$link = new mysqli("localhost","root","Bk3dGftO)QiQIPj5","playtime");

// vérifier la connection à la BD
if ($link->connect_error) {
    die("Erreur de connection : " . $link->connect_error);
}

//  autorisation
if ($type!="global") {
    if(isset($_SESSION['admin'])){
        if(($type == "admin" && $_SESSION['admin']!=1)) {
            header("Location: ../interface/404.php");
            die();
        }
    } else {
        header("Location: ../interface/404.php");
        die();
    }
}

// ************************************************************ \\
// *********************** Fonctions ************************** \\
// ************************************************************ \\

/**
 * 
 * @param mysqli $link instance de connextion avec la BD
 * @return int dernière valeur utiliser par l'autoincrement
 */
function getLastId($link): int
{
	$q = 'SELECT LAST_INSERT_ID()';
	return $link->query($q)->fetch_row()[0];
}

/**
 * getUser
 *
 * @param  mysqli $link instance de connextion avec la BD
 * @param  string $email email de l'utilisateur
 * @return array
 */
function getUser($link, string $email): array
{
    $q = "SELECT * FROM users WHERE email='$email'";
    $data = array(
        'iduser' => $link->query($q)->fetch_row()[0],
        'email' => $link->query($q)->fetch_row()[1],
        'pseudo' => str_replace("-", " ", $link->query($q)->fetch_row()[2]),
        'password' => $link->query($q)->fetch_row()[3],
        'avatar' => $link->query($q)->fetch_row()[4],
        'admin' => $link->query($q)->fetch_row()[5]
    );
    return $data;
}

/**
 * getGame
 *
 * @param  mysqli $link instance de connextion avec la BD
 * @param  int $idgame id du jeu
 * @return array
 */
function getGame($link,$idgame): array
{
    $q = "SELECT * FROM games WHERE idgame='$idgame'";
    
    $result = $link->query("SELECT DISTINCT fk_categorie FROM games INNER JOIN games_details ON idgame=fk_idgame WHERE idgame='$idgame'");
    $categories = array();
    while ($categorie = $result->fetch_column(0)) {
            $categories[] = $categorie;
    }

    $platforms = array();
    $result = $link->query("SELECT DISTINCT fk_platform FROM games INNER JOIN games_details ON idgame=fk_idgame WHERE idgame='$idgame'");
    while ($platform = $result->fetch_column(0)) {
        $platforms[] = $platform;
    }
    // Status
    $sql = "SELECT typestatus FROM status WHERE fk_idgame='$idgame' AND fk_iduser='".$_SESSION['iduser']."'";
    if($link->query($sql)->num_rows==0){
        $status = "";
    } else{
        $status = $link->query($sql)->fetch_row()[0];
    }

    $data = array(
        'idgame' => $link->query($q)->fetch_row()[0],
        'title' => $link->query($q)->fetch_row()[1],
        'description' => $link->query($q)->fetch_row()[2],
        'date' => $link->query($q)->fetch_row()[3],
        'developper' => $link->query($q)->fetch_row()[4],
        'categories'   => $categories,
        'platforms' => $platforms,
        'status' => $status
    );
    return $data;
}

/**
 * getCount
 *
 * @param  mysqli $link instance de connextion avec la BD
 * @param  int $id l'id de l'utilisateur
 * @param  string $status le type de statu
 * @return int
 */
function getCount($link,string $id,string $status): int
{
	$q = "SELECT * FROM status WHERE typestatus='$status' AND fk_iduser='$id'";
	return $link->query($q)->num_rows;
}

/**
 * banner
 *
 * @return void
 */
function banner()
{   
    $result = "";
    if (isset($_SESSION['error']) && !empty($_SESSION['error'])) {
        $error = $_SESSION['error'];
        $result = <<<HTML
        <div class="banner-box">
            <div class="banner banner-danger">
                <h2 class="title-lg maj text-r600">Erreur</h2>
                <p class="text-sm text-r600">$error</p>
            </div>
        </div>
HTML;
    } else if (isset($_SESSION['success']) && !empty($_SESSION['success'])) {
        $success = $_SESSION['success'];
        $result = <<<HTML
        <div class="banner-box">
            <div class="banner banner-success">
                <h2 class="title-lg maj text-g600">Succès</h2>
                <p class="text-sm text-g600">$success</p>
            </div>
        </div>
HTML;
    }
    unset($_SESSION['error'],$_SESSION['success']);
    return $result;
}

/**
 * Permet de vérifier les entrées saisie par l'utilisateur
 * 
 * @param mysqli $link instance de connextion avec la BD
 * @param string $page nom de la page à retourner en cas d'erreur
 * @param bool $email vérifier l'email ?
 * @param bool $pwd vérifier le mot de passe ?
 * @param string $pwd1 1er mot de passe
 * @param string $pwd1 2nd mot de passe
 * 
 * @return void
 */
function verify($link,string $page,bool $email=false,bool $pwd=false,$pwd1=null,$pwd2=null): void
{
    $error = false;
    if($email && !(preg_match('/[^@ \t\r\n]+@[^@ \t\r\n]+\.[^@ \t\r\n]+/',$_POST['email']))){
            $error = true;
            $_SESSION['error'] = "Le formulaire n'a pas été rempli correctement : email";
        }
    if($pwd && !(isset($_POST['pwd']))){
            $error = true;
            $_SESSION['error'] = "Le formulaire n'a pas été rempli correctement : pwd";
        }
    if($error){
        $link->close();
        // Redirection
        header("Location: ../interface/$page");
        die();
    } 
    if(($pwd1!=null && $pwd2!=null) && ($pwd1 != $pwd2)){
        $_SESSION['error'] = "Vous avez saisi deux mots de passe différents";
        // Redirection
        header("Location: ../interface/$page");
        die();
    }
}

/**
 * getPathImg
 *
 * @param  mixed $title
 * @return void
 */
function getPathImg($title)
{
    return "../../assets/img/cover/".str_replace(" ", "_", $title).".jpg";
}

?>