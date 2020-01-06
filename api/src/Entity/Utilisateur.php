<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\ResetPasswordAction;

/**
 * @ApiResource(
 *     normalizationContext={"groups" = {"get"}},
 *     itemOperations={
 *     "get"={
 *          "access_control"="is_granted('IS_AUTHENTICATED_FULLY')",
 *          "normalization_context"={
 *              "groups" = {"get"}
 *          }
 *     },
 *     "put"={
        "access_control"="is_granted('IS_AUTHENTICATED_FULLY') and object == user",
 *          "denormalization_context"={
 *          "groups" = {"put"}
 *          },
 *          "normalization_context"={
 *              "groups" = {"get"}
 *          }
 *     },
 *     "put-reset-password"={
            "access_control"="is_granted('IS_AUTHENTICATED_FULLY') and object == user",
 *          "method" = "PUT",
 *          "path"="/utilisateurs/{id}/reset-password",
 *          "controller" = ResetPasswordAction::class,
 *          "denormalization_context"={
 *          "groups" = {"put-reset-password"}
 *          },
 *          "validation_groups"={"put-reset-password"}
 *     }
 *  },
 *     collectionOperations={
 *     "post"={
            "denormalization_context"={
 *          "groups" = {"post"}
 *          },
 *          "normalization_context"={
 *              "groups" = {"get"}
 *          },
 *          "validation_groups"={"post"}
 *      },
 *     "get"={
            "normalization_context"={
 *              "groups" = {"get"}
 *          }
 *     }
 *  },
 *
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UtilisateurRepository")
 * @UniqueEntity("email")
 */
class Utilisateur implements UserInterface
{
    const ROLE_COMMENTATOR = 'ROLE_COMMENTATOR';
    const ROLE_WRITER = 'ROLE_WRITER';
    const ROLE_EDITOR = 'ROLE_EDITOR';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_SUPERADMIN = 'ROLE_SUPERADMIN';
    const ROLE_COACH = 'ROLE_COACH';

    const DEFAULT_ROLES = [self::ROLE_COMMENTATOR,'ROLE_USER'];
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"get", "get-comment-with-author"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({ "post","put","get-admin","get-owner"})
     * @Assert\NotBlank(groups={"post"})
     * @Assert\Email(groups={"post", "put"})
     */
    private $email;

    /**
     * @ORM\Column(type="simple_array", length=200)
     * @Groups({"post","get-admin","get-owner"})
     */
    private $roles;
    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Groups({"post"})
     * @Assert\NotBlank(groups={"post"})
     * @Assert\Regex(
     *     pattern="/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{7,}/",
     *     message="Password must be seven characteres long and contain at least one digit, one upper case",
     *     groups={"post"}
     * )
     */
    private $password;

    /**
     * @Assert\NotBlank(groups={"post"})
     * @Groups({"post"})
     * @Assert\Expression(
     *     "this.getPassword() === this.getRetypedPassword()",
     *     message="Passwords doesn't match",
     *     groups={"post"}
     * )
     */
    private $retypedPassword;

    /**
     * @Groups({"put-reset-password"})
     * @Assert\NotBlank(groups={"put-reset-password"})
     * @Assert\Regex(
     *     pattern="/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{7,}/",
     *     message="Password must be seven characteres long and contain at least one digit, one upper case",
     *     groups={"put-reset-password"}
     * )
     */
    private $newPassword;

    /**
     * @Assert\NotBlank(groups={"put-reset-password"})
     * @Groups({"put-reset-password"})
     * @Assert\Expression(
     *     "this.getNewPassword() === this.getNewRetypedPassword()",
     *     message="Passwords doesn't match",
     *     groups={"put-reset-password"}
     * )
     */
    private $newRetypedPassword;

    /**
     * @Groups({"put-reset-password"})
     * @Assert\NotBlank(groups={"put-reset-password"})
     * @UserPassword(groups={"put-reset-password"})
     */
    private $oldPassword;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"get", "post", "put","get-comment-with-author", "get-blog-post-with-author"})
     * @Assert\NotBlank(groups={"post"})
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"get","post","put", "get-comment-with-author", "get-blog-post-with-author"})
     * @Assert\NotBlank(groups={"post", "put"})
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\BlogPost", mappedBy="author")
     * @Groups({"get"})
     */
    private $blogPosts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="author")
     * @Groups({"get"})
     */
    private $comments;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $passwordChangeDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $confirmationToken;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Image")
     * @ApiSubresource()
     * @Groups({"post", "get", "put"})
     */
    private $profilePicture;

    public function __construct()
    {
        $this->blogPosts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->roles = self::DEFAULT_ROLES;
        $this->enabled = false;
        $this->confirmationToken = null;
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
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        //$roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        //$roles[] = 'ROLE_USER';

        //return array_unique($roles);
        return  $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|BlogPost[]
     */
    public function getBlogPosts(): Collection
    {
        return $this->blogPosts;
    }

    public function addBlogPost(BlogPost $blogPost): self
    {
        if (!$this->blogPosts->contains($blogPost)) {
            $this->blogPosts[] = $blogPost;
            $blogPost->setAuthor($this);
        }

        return $this;
    }

    public function removeBlogPost(BlogPost $blogPost): self
    {
        if ($this->blogPosts->contains($blogPost)) {
            $this->blogPosts->removeElement($blogPost);
            // set the owning side to null (unless already changed)
            if ($blogPost->getAuthor() === $this) {
                $blogPost->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
            }
        }

        return $this;
    }

    public function getRetypedPassword()
    {
        return $this->retypedPassword;
    }
    public function setRetypedPassword($retypedPassword): void
    {
        $this->retypedPassword = $retypedPassword;
    }


    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }


    public function setNewPassword($newPassword): void
    {
        $this->newPassword = $newPassword;
    }


    public function getNewRetypedPassword(): ?string
    {
        return $this->newRetypedPassword;
    }

    public function setNewRetypedPassword($newRetypedPassword): void
    {
        $this->newRetypedPassword = $newRetypedPassword;
    }

    public function getOldPassword(): ?string
    {
        return $this->oldPassword;
    }

    public function setOldPassword($oldPassword): void
    {
        $this->oldPassword = $oldPassword;
    }


    public function getPasswordChangeDate()
    {
        return $this->passwordChangeDate;
    }


    public function setPasswordChangeDate($passwordChangeDate): void
    {
        $this->passwordChangeDate = $passwordChangeDate;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled($enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken($confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    public function getProfilePicture()
    {
        return $this->profilePicture;
    }


    public function setProfilePicture($profilePicture): void
    {
        $this->profilePicture = $profilePicture;
    }


}
