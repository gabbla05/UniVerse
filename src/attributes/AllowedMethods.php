<?php

#[Attribute(Attribute::TARGET_METHOD)]
class AllowedMethods {
    public function __construct(public array $methods) {}
}