<?php

declare(strict_types=1);

namespace App\Entity;

class Type
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $code;
    /**
     * @var string
     */
    private $name;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Card>
     */
    private $cards;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->cards = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Type
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Type
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add card.
     *
     * @return Type
     */
    public function addCard(Card $card)
    {
        $this->cards[] = $card;

        return $this;
    }

    /**
     * Remove card.
     */
    public function removeCard(Card $card): void
    {
        $this->cards->removeElement($card);
    }

    /**
     * Get cards.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Card>
     */
    public function getCards()
    {
        return $this->cards;
    }
}
