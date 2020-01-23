<?php

namespace FriendsOfTwig\Twigcs\Scope;

use FriendsOfTwig\Twigcs\TwigPort\Token;

class Scope
{
    /**
     * @var array
     */
    private $children;

    /**
     * @var Scope|null
     */
    private $parent;

    /**
     * @var array
     */
    private $declarations;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $usages;

    /**
     * @var bool
     */
    private $isolated;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->children = [];
        $this->declarations = [];
        $this->usages = [];
        $this->isolated = false;
    }

    /**
     * When isolated, a scope won't be explored when looking for name usages.
     */
    public function isolate()
    {
        $this->isolated = true;
    }

    public function isIsolated(): bool
    {
        return $this->isolated;
    }

    public function spawn(string $name): self
    {
        $scope = new self($name);
        $scope->parent = $this;
        $this->children[] = $scope;

        return $scope;
    }

    public function leave(): self
    {
        return $this->parent ?? $this;
    }

    public function declare(string $name, Token $token)
    {
        $this->declarations[$name] = $token;
    }

    public function use(string $name)
    {
        $this->usages[] = $name;
    }

    public function getUnused(): array
    {
        $unused = [];

        foreach ($this->declarations as $name => $token) {
            if (!$this->isUsed($name)) {
                $unused[] = $token;
            }
        }

        foreach ($this->children as $child) {
            $unused = array_merge($unused, $child->getUnused());
        }

        return $unused;
    }

    public function isUsed(string $name): bool
    {
        if (in_array($name, $this->usages, true)) {
            return true;
        }

        foreach ($this->children as $child) {
            if (!$child->isIsolated() && $child->isUsed($name)) {
                return true;
            }
        }

        return false;
    }

    public function dump(int $tab = 0): string
    {
        $declarations = implode(', ', array_keys($this->declarations));
        $usages = implode(', ', $this->usages);

        $self = sprintf("%s : {D : %s} {U : %s} \n", $name ?? 'noname', $declarations, $usages);

        $children = '';

        foreach ($this->children as $child) {
            $children .= str_repeat(' ', $tab).$child->dump($tab + 4)."\n";
        }

        return $self.$children;
    }
}
