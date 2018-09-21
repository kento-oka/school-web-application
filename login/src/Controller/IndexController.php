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
                    && false !== ($user = $this->getUserColumn($id))
                ){
                    if(password_verify($pw, $user["password"])){
                        session_regenerate_id(true);

                        $_SESSION["user_id"]    = $user["id"];
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
     *  path="/student/student.php",
     *  host="*",
     *  name="student"
     * )
     */
    public function student(ServerRequestInterface $request){
        session_start();

        if(
            !array_key_exists("user_id", $_SESSION)
            || false === ($user = $this->getUserColumn($_SESSION["user_id"]))
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
            !array_key_exists("user_id", $_SESSION)
            || false === ($user = $this->getUserColumn($_SESSION["user_id"]))
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


    protected function getUserColumn(string $id){
        try{
            $pdo    = new \PDO(
                "sqlite:" . __DIR__ . "\..\..\db.sqlite",
                null,
                null,
                [
                    \PDO::ATTR_ERRMODE  => \PDO::ERRMODE_EXCEPTION,
                ]
            );

            $stmt   = $pdo->prepare("SELECT * from login_user WHERE id = :id");

            $stmt->bindValue(":id", $id);

            if(false !== $stmt->execute()){
                $row    = $stmt->fetch();

                if(time() >= $row["deleted_at"]){
                    return $row;
                }
            }
        }catch(\Exception $e){
            echo $e->getMessage();die;
        }

        return false;
    }
}