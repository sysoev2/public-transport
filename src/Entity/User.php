<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ["email"], message: "There is already an account with this email")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ROLE_ADMIN'])]
    private int $id;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['PUBLIC', 'ROLE_ADMIN', 'USER_SELF'])]
    private string $email;

    #[ORM\Column(type: "json")]
    #[Groups(['PUBLIC', 'ROLE_ADMIN', 'USER_SELF'])]
    private $roles = [];

    /**
     * @var string The hashed password
     */
    #[Assert\NotBlank]
    #[ORM\Column]
    #[Groups(['ROLE_ADMIN'])]
    private string $password;

    #[ORM\ManyToMany(targetEntity: Transport::class)]
    #[JoinTable(name: "favorite_transport")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "transport_id", referencedColumnName: "id")]
    private ?\Doctrine\Common\Collections\Collection $transports = null;


    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['ROLE_ADMIN', 'USER_SELF'])]
    private string $api_token;

    public function __construct()
    {
        $this->transports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string)$this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getApiToken(): ?string
    {
        return $this->api_token;
    }

    public function setApiToken(?string $api_token): self
    {
        $this->api_token = $api_token;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, Transport>
     */
    public function getTransports(): \Doctrine\Common\Collections\Collection
    {
        return $this->transports;
    }

    public function addTransport(Transport $transport): self
    {
        if (!$this->transports->contains($transport)) {
            $this->transports->add($transport);
        }

        return $this;
    }

    public function removeTransport(Transport $transport): self
    {
        $this->transports->removeElement($transport);

        return $this;
    }

}
