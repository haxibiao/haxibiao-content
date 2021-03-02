<?php

/**
 * 是否开启答题模块
 *
 * @return boolean
 */
function enable_question()
{
    return is_null(config('content.enable_question')) || config('content.enable_question') === true;
}
