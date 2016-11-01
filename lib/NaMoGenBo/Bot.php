<?php

/*
 * This file is part of NaMoGenBo
 */
namespace NaMoGenBo;

use Huxtable\Bot\Corpora;
use Huxtable\Core\Config;
use Huxtable\Core\File;
use Huxtable\Core\Utils;

class Bot extends \Huxtable\Bot\Bot
{
	use \Huxtable\Bot\Corpora\Consumer;
	use \Huxtable\Bot\History\Consumer;
	use \Huxtable\Bot\Twitter\Consumer;
}
