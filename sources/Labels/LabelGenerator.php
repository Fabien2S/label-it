<?php

namespace LabelIT\Labels;

use LabelIT\Drawing\ImageDrawer;
use LabelIT\Items\Item;

interface LabelGenerator
{
    function generate(ImageDrawer $drawer, Item $item);
}