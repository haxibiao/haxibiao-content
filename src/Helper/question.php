<?php

/**
 * 是否开启答题模块
 */
function enable_question()
{
    return config('content.enable.question', true);
}
