<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2023 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\validate;

/**
 * Class ValidateRuleSet
 * @package think\validate
 */
class ValidateRuleSet
{
    /**
     * 构造方法
     * @access public
     */
    public function __construct(protected array $rules = [])
    {
    }

    /**
     * 添加验证因子
     * @access public
     * @param  string    $name  验证名称
     * @param  mixed     $rule  验证规则
     * @param  string    $msg   提示信息
     * @return $this
     */
    public static function rules(array $rules)
    {
        $ruleSet = new static($rules);

        return $ruleSet;
    }

    /**
     * 获取验证因子
     * @access public
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

}
