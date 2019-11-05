<?php

namespace App\Controller;

use FOS\UserBundle\Model\UserManagerInterface;;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;

/**
 * @Route("user-api")
 */
class UserController extends AbstractController
{

    const FIRST_NAME_VALIDATION_MESSAGE = 'firstName должно быть длиннее 2-х символов';
    const LAST_NAME_VALIDATION_MESSAGE = 'lastName должно быть длиннее 2-х символов';
    const PASSWORD_VALIDATION_MESSAGE = 'password не должен быть пустым';
    const PHONE_VALIDATION_MESSAGE = 'phone должен начинаться с "+" и состоять из не менее 7 цифр';
    const EMAIL_VALIDATION_MESSAGE = 'email должен быть корректным адресом электронной почты';


    /**
     * Регистрация пользователя
     *
     * Авторизация не нужна.
     *
     * @Route("/register", name="user_register",  methods={"POST"})
     * @param Request $request
     * @param UserManagerInterface $userManager
     * @return JsonResponse
     *
     * @SWG\Response(
     *     response=200,
     *     description="Пользователь {email} успешно зарегистрирован!"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Ошибки валидации, ошибки работы с БД"
     * )
     * @SWG\Parameter(
     *     name="user JSON",
     *     in="body",
     *     type="array",
     *     description="JSON with user data",
     *     @SWG\Schema(
     *          type="array",
     *          items={"email","password","firstName","lastName","phone"},
     *          example={"email"="some2@email.ru","password"="somepassxcv","firstName"="1dsds","lastName"="jghjgh","phone"="+375293021197"}
     *     )
     * )
     */
    public function register(Request $request, UserManagerInterface $userManager)
    {
        $data = $this->prepareRequestData($request->getContent());

        $violations = $this->validateData($data, true);
        if ($violations->count() > 0) {
            return new JsonResponse(["error" => (string)$violations], 500);
        }

        $user = new User();
        $user
            ->setUsername($data['email'])
            ->setPlainPassword($data['password'])
            ->setEmail($data['email'])
            ->setFirstName($data['firstName'])
            ->setLastName($data['lastName'])
            ->setPhone($data['phone'])
            ->setEnabled(true)
            ->setRoles(['ROLE_USER'])
            ->setSuperAdmin(false)
        ;

        try {
            $userManager->updateUser($user);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => $e->getMessage()], 500);
        }

        return new JsonResponse(["success" => 'Пользователь ' . $user->getUsername(). ' успешно зарегистрирован!'], 200);
    }

    /**
     * Редактирование пользователя
     *
     * Только для авторизованных пользователей. Можно редактировать только самого себя.
     *
     * @Route("/edit", name="user_edit",  methods={"POST"})
     * @param Request $request
     * @param UserManagerInterface $userManager
     * @param TokenStorageInterface $tokenStorage
     * @return JsonResponse
     *
     * @SWG\Response(
     *     response=200,
     *     description="Пользователь {email} успешно изменен!"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Ошибки валидации, ошибки работы с БД, пользователь не существует"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Не корректный токен"
     * )
     * @SWG\Parameter(
     *     name="user JSON",
     *     in="body",
     *     type="array",
     *     description="JSON with user data",
     *     @SWG\Schema(
     *          type="array",
     *          items={"email","password","firstName","lastName","phone"},
     *          example={"email"="some2@email.ru","password"="somepassxcv","firstName"="1dsds","lastName"="jghjgh","phone"="+375293021197"}
     *     )
     * )
     * @Security(name="Bearer")
     */
    public function edit(Request $request, UserManagerInterface $userManager, TokenStorageInterface $tokenStorage)
    {
        $data = $this->prepareRequestData($request->getContent());

        $violations = $this->validateData($data, false);
        if ($violations->count() > 0) {
            return new JsonResponse(["error" => (string)$violations], 500);
        }

        $user = $tokenStorage->getToken()->getUser();

        if ($user === null) {
            return new JsonResponse(["error" => 'Пользователь ' . $data['email'] . ' не существует'], 500);
        } else {

            if ($data['firstName']) $user->setFirstName($data['firstName']);
            if ($data['lastName']) $user->setLastName($data['lastName']);
            if ($data['password']) $user->setPlainPassword($data['password']);
            if ($data['phone']) $user->setPhone($data['phone']);
            if ($data['email']) {
                $user->setEmail($data['email']);
                $user->setUsername($data['email']);
            }

        }

        try {
            $userManager->updateUser($user);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => $e->getMessage()], 500);
        }

        return new JsonResponse(["success" => 'Пользователь ' . $user->getUsername(). ' успешно изменен!'], 200);
    }

    /**
     * Список пользователей
     *
     * Авторизация не нужна
     *
     * @Route("/list", name="user_list",  methods={"GET"})
     * @param UserManagerInterface $userManager
     * @return JsonResponse
     *
     * @SWG\Response(
     *     response=200,
     *     description="Список пользователей"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="ошибки работы с БД"
     * )
     */
    public function usersList(UserManagerInterface $userManager)
    {
        $output = array();
        try {
            $users = $userManager->findUsers();
            foreach ($users as $user) {
                $output[] = array(
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'roles' => $user->getRoles(),
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(["error" => $e->getMessage()], 500);
        }

        return new JsonResponse($output, 200);
    }

    /**
     * Удаление пользователя
     *
     * Только администратор
     *
     * @Route("/delete", name="user_delete",  methods={"DELETE"})
     * @param Request $request
     * @param UserManagerInterface $userManager
     * @param TokenStorageInterface $tokenStorage
     * @return JsonResponse
     *
     * @SWG\Response(
     *     response=200,
     *     description="Пользователь {email} успешно удален!"
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Ошибки валидации, ошибки работы с БД, пользователь не существует"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Нет прав на удаление"
     * )
     * @SWG\Parameter(
     *     name="user JSON",
     *     in="body",
     *     type="array",
     *     description="JSON with user data",
     *     @SWG\Schema(
     *          type="array",
     *          items={"email"},
     *          example={"email"="some2@email.ru"}
     *     )
     * )
     * @Security(name="Bearer")
     */
    public function delete(Request $request, UserManagerInterface $userManager, TokenStorageInterface $tokenStorage)
    {
        $user = $tokenStorage->getToken()->getUser();
        if (!$user->hasRole('ROLE_ADMIN'))
            return new JsonResponse(["error" => "У вас нет прав для удаления пользователей"], 401);

        $data = $this->prepareRequestData($request->getContent());

        if (!$data['email'])
            return new JsonResponse(["error" => "Нужно указать email"], 500);

        try {
            $userForDelete = $userManager->findUserByEmail($data['email']);
            if ($userForDelete === null)
                return new JsonResponse(["error" => "Пользователь не сушествует"], 500);
            $userManager->deleteUser($userForDelete);
        } catch (\Exception $e) {
            return new JsonResponse(["error" => $e->getMessage()], 500);
        }

        return new JsonResponse(["success" => 'Пользователь ' . $data['email'] . ' успешно удален!'], 200);
    }

    private function prepareRequestData($json): array {
        $data = json_decode($json, true);

        $data['firstName'] = $data['firstName'] ?? false;
        $data['lastName'] = $data['lastName'] ?? false;
        $data['password'] = $data['password'] ?? false;
        $data['phone'] = $data['phone'] ?? false;
        $data['email'] = $data['email'] ?? false;

       return $data;
    }

    private function validateData($data, bool $isNew = false) {

        $validator = Validation::createValidator();

        $assertsArray = array();

        if ($data['firstName'] || $isNew) {
            $assertsArray['firstName'][] = new Assert\Length(array('min' => 2, "minMessage" => self::FIRST_NAME_VALIDATION_MESSAGE));
            if ($isNew)
                $assertsArray['firstName'][] = new Assert\NotBlank(array("message" => self::FIRST_NAME_VALIDATION_MESSAGE));
        } else {
            $assertsArray['firstName'] = [];
        }

        if ($data['lastName'] || $isNew) {
            $assertsArray['lastName'][] = new Assert\Length(array('min' => 2, "minMessage" => self::LAST_NAME_VALIDATION_MESSAGE));
            if ($isNew)
                $assertsArray['lastName'][] = new Assert\NotBlank(array("message" => self::LAST_NAME_VALIDATION_MESSAGE));
        } else {
            $assertsArray['lastName'] = [];
        }

        if ($data['password'] || $isNew) {
            $assertsArray['password'][] = new Assert\NotBlank(array("message" => self::PASSWORD_VALIDATION_MESSAGE));
        } else {
            $assertsArray['password'] = [];
        }

        if ($data['phone'] || $isNew) {
            $assertsArray['phone'][] = new Assert\Regex(array('pattern' => "/^\+[0-9]{7,}$/", "message" => self::PHONE_VALIDATION_MESSAGE));
            if ($isNew)
                $assertsArray['phone'][] = new Assert\NotBlank(array("message" => self::PHONE_VALIDATION_MESSAGE));
        } else {
            $assertsArray['phone'] = [];
        }

        if ($data['email'] || $isNew) {
            $assertsArray['email'][] = new Assert\Email(array("message" => self::EMAIL_VALIDATION_MESSAGE));
        } else {
            $assertsArray['email'] = [];
        }

        $constraint = new Assert\Collection($assertsArray);

        return $validator->validate($data, $constraint);

    }
}