<?php namespace Darryldecode\Cart;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/17/2015
 * Time: 11:03 AM
 */

use Darryldecode\Cart\Helpers\Helpers;
use Illuminate\Support\Collection;

class ItemCollection extends Collection
{

    /**
     * Sets the config parameters.
     *
     * @var
     */
    protected $config;

    /**
     * ItemCollection constructor.
     * @param array|mixed $items
     * @param $config
     */
    public function __construct($items, $config = [])
    {
        parent::__construct($items);

        $this->config = $config;
    }

    /**
     * get the sum of price
     *
     * @return mixed|null
     */
    public function getPriceSum()
    {
        return Helpers::formatValue($this->price * $this->quantity, $this->config['format_numbers'], $this->config);
    }


    /**
     * Returns the item total weight
     *
     * @return float|int|null
     */
    public function getTotalWeight()
    {
        if($this->attributes->has('gross_weight') && $this->attributes->gross_weight){
            return round($this->attributes->gross_weight * $this->quantity,3);
        }elseif($this->attributes->has('carton_weight') && $this->attributes->carton_weight && $this->attributes->has('pcs_per_carton') && $this->attributes->pcs_per_carton){
            $cartons = ceil($this->quantity / $this->attributes->pcs_per_carton);
            return round($this->attributes->carton_weight * $cartons,3);
        }
        return null;
    }
    /**
     * get the sum of price with taxes
     *
     * @return mixed|null
     */
    public function getPriceSumWithTaxes()
    {
        if($this->attributes->has('is_vat_inclusive') && $this->attributes->has('vat_percentage') && $this->attributes->is_vat_inclusive == false){
            $priceSum = addVat($this->price, $this->attributes->vat_percentage) * $this->quantity;
            return Helpers::formatValue($priceSum, $this->config['format_numbers'], $this->config);
        }else{
            return Helpers::formatValue($this->price * $this->quantity, $this->config['format_numbers'], $this->config);
        }
    }

    /**
     * get the price with taxes
     *
     * @return mixed|null
     */
    public function getPriceWithTaxes()
    {
        if($this->attributes->has('is_vat_inclusive') && $this->attributes->has('vat_percentage') && $this->attributes->is_vat_inclusive == false){
            $priceSum = addVat($this->price, $this->attributes->vat_percentage);
            return Helpers::formatValue($priceSum, $this->config['format_numbers'], $this->config);
        }else{
            return Helpers::formatValue($this->price, $this->config['format_numbers'], $this->config);
        }
    }

    /**
     * get the price without taxes
     *
     * @return mixed|null
     */
    public function getPriceWithoutTaxes()
    {
        if($this->attributes->has('is_vat_inclusive') && $this->attributes->has('vat_percentage') && $this->attributes->is_vat_inclusive == true){
            $priceSum = removeVAT($this->price, $this->attributes->vat_percentage);
            return Helpers::formatValue($priceSum, $this->config['format_numbers'], $this->config);
        }else{
            return Helpers::formatValue($this->price, $this->config['format_numbers'], $this->config);
        }
    }

    /**
     * get the sum of price without taxes
     *
     * @return mixed|null
     */
    public function getPriceSumWithoutTaxes()
    {
        if($this->attributes->has('is_vat_inclusive') && $this->attributes->has('vat_percentage') && $this->attributes->is_vat_inclusive == true){
            $priceSum = removeVAT($this->price, $this->attributes->vat_percentage) * $this->quantity;
            return Helpers::formatValue($priceSum, $this->config['format_numbers'], $this->config);
        }else{
            return Helpers::formatValue($this->price * $this->quantity, $this->config['format_numbers'], $this->config);
        }
    }


    public function __get($name)
    {
        if ($this->has($name) || $name == 'model') {
            return !is_null($this->get($name)) ? $this->get($name) : $this->getAssociatedModel();
        }
        return null;
    }

    /**
     * return the associated model of an item
     *
     * @return bool
     */
    protected function getAssociatedModel()
    {
        if (!$this->has('associatedModel')) {
            return null;
        }

        $associatedModel = $this->get('associatedModel');

        return with(new $associatedModel())->find($this->get('id'));
    }

    /**
     * check if item has conditions
     *
     * @return bool
     */
    public function hasConditions()
    {
        if (!isset($this['conditions'])) return false;
        if (is_array($this['conditions'])) {
            return count($this['conditions']) > 0;
        }
        $conditionInstance = "Darryldecode\\Cart\\CartCondition";
        if ($this['conditions'] instanceof $conditionInstance) return true;

        return false;
    }

    /**
     * check if item has conditions
     *
     * @return mixed|null
     */
    public function getConditions()
    {
        if (!$this->hasConditions()) return [];
        return $this['conditions'];
    }

    /**
     * get the single price in which conditions are already applied
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceWithConditions($formatted = true)
    {
        $originalPrice = $this->price;
        $newPrice = 0.00;
        $processed = 0;

        if ($this->hasConditions()) {
            if (is_array($this->conditions)) {
                foreach ($this->conditions as $condition) {
                    ($processed > 0) ? $toBeCalculated = $newPrice : $toBeCalculated = $originalPrice;
                    $newPrice = $condition->applyCondition($toBeCalculated);
                    $processed++;
                }
            } else {
                $newPrice = $this['conditions']->applyCondition($originalPrice);
            }

            return Helpers::formatValue($newPrice, $formatted, $this->config);
        }
        return Helpers::formatValue($originalPrice, $formatted, $this->config);
    }

    /**
     * get the sum of price in which conditions are already applied
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceSumWithConditions($formatted = true)
    {
        return Helpers::formatValue($this->getPriceWithConditions(false) * $this->quantity, $formatted, $this->config);
    }
}
