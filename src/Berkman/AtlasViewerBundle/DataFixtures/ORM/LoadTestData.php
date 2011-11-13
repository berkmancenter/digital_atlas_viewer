<?php

namespace Berkman\AtlasViewerBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

use Berkman\AtlasViewerBundle\Entity\Person;
use Berkman\AtlasViewerBundle\Entity\Atlas;

class LoadTestData implements FixtureInterface
{
	public function load($manager)
	{

        // create a user
        $user = new Person();
        $user->setUsername('justin');
        $user->setUsernameCanonical('justin');
        $user->setEmail('justin@example.com');
        $user->setEmailCanonical('justin@example.com');
        $user->setAlgorithm('sha512');
        $user->addRole('ROLE_USER');
	$user->addRole('ROLE_SUPER_ADMIN');
        $user->setEnabled(true);
 
        $encoder = new MessageDigestPasswordEncoder();
        $password = $encoder->encodePassword('password', $user->getSalt());
        $user->setPassword($password);

        $manager->persist($user);

        $atlas = new Atlas();
        $atlas->setName('Test');
        $atlas->setDescription('Test');
        $atlas->setUrl('http://localhost/DAV/camb1900.zip');
        $atlas->setDefaultEpsgCode(102686);
        $atlas->setCreated(new \DateTime('now'));
        $atlas->setUpdated(new \DateTime('now'));
        $atlas->setOwner($user);

        $manager->persist($atlas);
		$manager->flush();
	}
}
