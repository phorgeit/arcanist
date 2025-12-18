<?php

abstract class ArcanistSingleSourceConfigOption
  extends ArcanistConfigOption {

  /**
   * @param array<ArcanistConfigurationSourceValue> $list
   */
  public function getValueFromStorageValueList(array $list) {
    assert_instances_of($list, ArcanistConfigurationSourceValue::class);

    $source_value = last($list);
    $storage_value = $this->getStorageValueFromSourceValue($source_value);

    return $this->getValueFromStorageValue($storage_value);
  }

  public function getValueFromStorageValue($value) {
    return $value;
  }

}
