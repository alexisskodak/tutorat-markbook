<?php

namespace App\DataPersister;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserDataPersister implements DataPersisterInterface
{
    /**
     * @var UserPasswordHasherInterface
     */
    private $encoder;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * UserPersister constructor.
     * @param UserPasswordHasherInterface $encoder
     * @param EntityManagerInterface $manager
     */
    public function __construct(UserPasswordHasherInterface $encoder, EntityManagerInterface $manager){

        $this->encoder = $encoder;
        $this->manager = $manager;

    }

    /**
     *********************************************
     * Détermine si oui ou non notre objet $data
     * est une instance de App/Entity/User.
     *********************************************
     * @param $data
     * @return bool
     */
    public function supports($data): bool
    {
        return $data instanceof User;
    }

    /**
     *****************************************************************
     * Méthode venant encoder le  plain password de l'utilisateur
     * avant de pousser ses informations en BDD.
     *****************************************************************
     * @param $data
     * @return object|void
     */
    public function persist($data)
    {
        if($data->getPlainPassword()){

            $data->setPassword($this->encoder->hashPassword($data, $data->getPlainPassword()));
            $data->eraseCredentials();

        }

        $this->manager->persist($data);
        $this->manager->flush();
    }

    /**
     ***********************************************
     * Cette méthode précise quoi faire au moment
     * de la suppression de cet objet $data.
     ***********************************************
     * @param $data
     */
    public function remove($data)
    {
        $this->manager->remove($data);
        $this->manager->flush();
    }
}