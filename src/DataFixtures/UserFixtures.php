<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $passwordEncoder = null;
    public function __construct(UserPasswordEncoderInterface $passwordEncoder){
    $this->passwordEncoder = $passwordEncoder;
    }
    public function load(ObjectManager $manager): void
    {
        $superadmin = new User();
        $superadmin->setUsername('superadmin');
        $superadmin->setRoles(array('ROLE_SUPER_ADMIN'));
        $password= $this->passwordEncoder->encodePassword($superadmin, 'superadmin');
        $superadmin->setPassword($password);
        $manager->persist($superadmin);

        $admin = new User();
        $admin->setUsername('admin');
        $admin->setRoles(array('ROLE_ADMIN'));
        $password= $this->passwordEncoder->encodePassword($admin, 'admin');
        $admin->setPassword($password);
        $manager->persist($admin);

        $user = new User;
        $user->setUsername('user');
        $user->setRoles(array('ROLE_USER'));
        $password= $this->passwordEncoder->encodePassword($user, 'user');
        $user->setPassword($password);
        $manager->persist($user);


        $manager->flush();
    }
}
