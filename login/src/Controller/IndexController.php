<?php
namespace App\Controller;

use Fratily\Router\Annotation\Route;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @Route(
 *  path="web02006/login"
 * )
 */
class IndexController extends \Fratily\Bundle\Framework\Controller\AbstractController {

    /**
     * @Route(
     *  path="/login.php",
     *  host="*",
     *  methods={"GET", "POST"},
     *  name="login"
     * )
     */
    public function login(ServerRequestInterface $request, $_route){
        session_start();

        if("POST" === $request->getMethod()){
            $token  = filter_input(INPUT_POST, "csrf_token");
            $id     = filter_input(INPUT_POST, "id");
            $pw     = filter_input(INPUT_POST, "pw");

            if(false !== $token && false !== $id && false !== $pw){
                if(
                    $_SESSION["csrf_token"] === $token
                    && false !== ($user = $this->getUser($id, false))
                ){
                    if(password_verify($pw, $user["signin_pw"])){
                        session_regenerate_id(true);

                        $_SESSION["signin_id"]  = $user["signin_id"];
                        $response               = $this->generateResponse(302, "");

                        return $response->withHeader(
                            "Location",
                            (string)$this->generateUrl($request, $user["type"])
                        );
                    }
                }
            }
        }

        $_SESSION["csrf_token"] = bin2hex(random_bytes(64));

        return $this->render(
            "login.html.twig",
            [
                "token"     => $_SESSION["csrf_token"],
                "action"    => $this->generateUrl($request, $_route),
            ]
        );
    }

    /**
     * @Route(
     *  path="/logout.php",
     *  host="*",
     *  name="logout"
     * )
     */
    public function logout(ServerRequestInterface $request){
        session_start();

        session_destroy();

        return $this->generateResponse(302, "")->withHeader(
            "Location",
            (string)$this->generateUrl($request, "login")
        );
    }

    /**
     * @Route(
     *  path="/add.php",
     *  host="*",
     *  name="add"
     * )
     */
    public function add(ServerRequestInterface $request){
        session_start();

        $error  = null;

        if("POST" === $request->getMethod()){
            $token  = filter_input(INPUT_POST, "csrf_token");
            $id     = filter_input(INPUT_POST, "id");
            $pw     = filter_input(INPUT_POST, "pw");
            $name   = filter_input(INPUT_POST, "name");
            $type   = filter_input(INPUT_POST, "type");

            if(false !== $token && false !== $id && false !== $pw && false !== $name && false !== $type){
                if(
                    $_SESSION["csrf_token"] === $token
                    && 1 === preg_match("/\A[0-9A-Z-_.]{4,20}\z/i", $id)
                    && 1 === preg_match("/\A[0x21-0x7e]{4,70}\z/", $pw)
                    && 1 === preg_match("/\A.+?\z/u", $name) // どうにかする
                    && in_array($type, ["student", "teacher"])
                    && false === $this->getUser($id)
                ){
                    $pw     = password_hash($pw, PASSWORD_BCRYPT);
                    $pdo    = $this->getConnection();

                    try{
                        $stmt   = $pdo->prepare(
                            "INSERT INTO login_user(signin_id, signin_pw, name, type, created_at)"
                            . " VALUES (:id, :pw, :name, :type, :created)"
                        );

                        $stmt->bindValue(":id", $id);
                        $stmt->bindValue(":pw", $pw);
                        $stmt->bindValue(":name", $name);
                        $stmt->bindValue(":type", $type);
                        $stmt->bindValue(":created", time());

                        if(false === $stmt->execute()){
                            throw new \Exception();
                        }

                        $response   = $this->generateResponse(302, "");

                        return $response->withHeader(
                            "Location",
                            (string)$this->generateUrl($request, "login")
                        );
                    }catch(\Exception $e){

                    }
                }
            }
        }

        $_SESSION["csrf_token"] = bin2hex(random_bytes(64));

        return $this->render(
            "add.html.twig",
            [
                "token"     => $_SESSION["csrf_token"],
                "action"    => $this->generateUrl($request, $_route),
            ]
        );
    }

    /**
     * @Route(
     *  path="/student/student.php",
     *  host="*",
     *  name="student"
     * )
     */
    public function student(ServerRequestInterface $request){
        session_start();

        if(
            !array_key_exists("signin_id", $_SESSION)
            || false === ($user = $this->getUser($_SESSION["signin_id"]))
            || "student" !== $user["type"]
        ){
            return $this->generateResponse(302, "")->withHeader(
                "Location",
                (string)$this->generateUrl($request, "logout")
            );
        }

        return $this->render(
            "student.html.twig",
            [
                "user"      => $user,
                "logout"    => $this->generateUrl($request, "logout"),
            ]
        );
    }

    /**
     * @Route(
     *  path="/teacher/teacher.php",
     *  host="*",
     *  name="teacher"
     * )
     */
    public function teacher(ServerRequestInterface $request){
        session_start();

        if(
            !array_key_exists("signin_id", $_SESSION)
            || false === ($user = $this->getUser($_SESSION["signin_id"]))
            || "teacher" !== $user["type"]
        ){
            return $this->generateResponse(302, "")->withHeader(
                "Location",
                (string)$this->generateUrl($request, "logout")
            );
        }

        return $this->render(
            "teacher.html.twig",
            [
                "user"      => $user,
                "logout"    => $this->generateUrl($request, "logout"),
            ]
        );
    }

    /**
     * データベースコネクションを取得する
     *
     * @return  \PDO|false
     */
    protected function getConnection(){
        try{
            return new \PDO(
                $_ENV["DB_DSN"],
                $_ENV["DB_USER"],
                $_ENV["DB_PASS"],
                [
                    \PDO::ATTR_ERRMODE  => \PDO::ERRMODE_EXCEPTION,
                ]
            );
        }catch(\PDOException $e){
            return false;
        }
    }

    /**
     * ユーザーを取得する
     *
     * @param   string  $id
     *  ユーザーのサインインID
     * @param   bool    $getDeleted
     *  削除されたユーザーも取得するか
     *
     * @return  mixed[]|false
     *  エラーが発生したりユーザーが存在しなければfalseを返す
     */
    protected function getUser(string $id, bool $getDeleted = false){
        try{
            $sql    = "SELECT * from login_user WHERE signin_id = :id";

            if(false === $getDeleted){
                $sql    .= " AND deleted_at IS NULL";
            }

            $stmt   = $this->getConnection()->prepare($sql);

            $stmt->bindValue(":id", $id);

            if(false === $stmt->execute()){
                throw new \Exception();
            }

            return $stmt->fetch();
        }catch(\Exception $e){
            return false;
        }
    }
}