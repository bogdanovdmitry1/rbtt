<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user
            ->setFirstName('Admin')
            ->setLastName('Admin')
            ->setEmail('admin@localhost.by')
            ->setUsername('admin@localhost.by')
            ->setRoles(['ROLE_ADMIN'])
            ->setPlainPassword('adminpwd')
            ->setPhone('+375290000000')
            ->setEnabled(true)
            ->setSuperAdmin(true)
        ;
        $manager->persist($user);

        $manager->flush();
    }
}
