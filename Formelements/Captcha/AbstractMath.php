<?php
declare(strict_types=1);

/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: AbstractMath.php
 * Created: 16.08.2022 
 */


namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class AbstractMath extends AbstractTextCaptcha
{
    protected string $result = '';
    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->setCaptchaContent($this->createRandomCalculation()); // set a random calculation as content
    }

    protected function calculate(int $varOne, string $operator, int $varTwo): string
    {
        $result = match ($operator) {
            '+' => $varOne + $varTwo,
            '-' => $varOne - $varTwo,
            '*' => $varOne * $varTwo,
            default => 0,
        };
        return (string) $result;
    }

    /**
     * Create the random calculation with numbers between 1 and 9
     * @return string
     */
    protected function createRandomCalculation(): string
    {
        $operators = ['+', '-', '*'];
        $randOperator = $operators[rand(0,2)];
        $num1 = rand(1,9);
        $num2 = rand(1,9);
        $this->result = $this->calculate($num1,$randOperator, $num2);
        return $num1.$randOperator.$num2;
    }

}