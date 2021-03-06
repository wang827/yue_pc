<?php

class AgentbookAction extends GlobalAction {

    //作品查看
    public function index() {
        //作品类型
        $type = BooktypeAction::booktype();
        $this->assign('type', $type);
        A('Gongju')->getauthorizationweb($this->to[web_id]);
        //post查询
        if ($this->isPost()) {
            if (!empty($_POST[keyword])) {
                $_POST[keyword] = trim($_POST[keyword]);
                if ($_POST[search_type] == 1) {
                    $where['book_name'] = array('like', "%$_POST[keyword]%");
                }elseif($_POST[search_type] == 2){
                    $where['author_name'] = array('like', "%$_POST[keyword]%");
                }else{
                    if($_POST[keyword]=="阅庭"){
                        $where['cp_name'] = "";
                    }else{
                        $where['cp_name'] = array('like', "%$_POST[keyword]%");
                    }     
                }
            }
            if (!empty($_POST[type_id])) {
                $where['type_id'] = $_POST[type_id];
            }

            if (!empty($_POST[state])) {
                $where['state'] = $_POST[state];
            }
        }
        //get提交
        if ($this->isGet()) {
            if (!empty($_GET[keyword])) {
                $_GET[keyword] = trim(urldecode($_GET[keyword]));
                if ($_GET[search_type] == 1) {
                    $where['book_name'] = array('like', "%$_GET[keyword]%");
                } elseif($_GET[search_type] == 2){
                    $where['author_name'] = array('like', "%$_GET[keyword]%");
                }else{
                    $where['cp_name'] = array('like', "%$_GET[keyword]%");
                }
            }
            if (!empty($_GET[type_id])) {
                $where['type_id'] = $_GET[type_id];
            }

            if (!empty($_GET[state])) {
                $where['state'] = $_GET[state];
            }
        }
        //数据处理
        $bbb = M('Book');
        import('ORG.Util.Page'); // 导入分页类       
        //统计条数开始
        // $where['web_id'] = $this->to[web_id];
        $where['fu_web'] = 0;
        // $where['fu_web'] = 0; //不是授权站书籍   
        // $where['author_id'] = array('neq', 0); //不等于0就是有作者真实的书
        $where['is_show'] = 1; //显示
        $where['words'] = array('gt', 0); //字数大于0
        $where['chapter'] = array('gt', 0); //章节大于0
        //$where['cp_name'] = array('neq', '阅庭'); //阅庭第三方不给予授权
        $where['fu_book'] = array('not in', '3922,7967'); //单独的书
        $count = $bbb->where($where)->count(); // 查询满足要求的总记录数
        $nums = isset($_GET['nums']) ? $_GET['nums'] : 500;//默认显示15条记录
        $Page = new Page($count, $nums); // 实例化分页类 传入总记录数和每页显示的记录数               
        $book = $bbb->where($where)->field('book_id,fu_book,book_name,author_name,cp_name,level,signing,state,vip,money,is_show,audit,words,time')->order('time desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('book', $book);
        //翻页样式
        $Page->setConfig('theme', "<div class=\"pageSelect\"> <span>共 <b>%totalRow%</b> 条 当前 <b>%nowPage%</b>/%totalPage% 页</span><div class=\"pageWrap\">%prePage%%upPage%%linkPage%%downPage%%nextPage%</div></div>");
        $show = $Page->show(); // 分页显示输出
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('count', $count);
        $this->assign('nums', $nums);
        $this->display();
    }


    //添加书单
    public function webbook() {
        if ($this->isPost()) {
            $web = M('Web');
            $myweb = $web->where(array('web_id' => $_POST[web_id]))->field('web_id,web_name')->field('web_id')->find();
            if (is_array($myweb)) {
                //准备模型
                $book = M('book');
                $bang = M('BookStatistical'); //作品榜单
                $value = $_POST[checkbox];
                @$zhi = implode(',', $value);
                $arr = explode(',', $zhi);
                foreach ($arr as $key => $value) {
                    //查询是否已经授权了
                    $isbook = $book->where(array('web_id' => $_POST[web_id], 'fu_book' => $value))->field('book_id')->find();
                    if (is_array($isbook)) {
                        continue;
                        // $this->error("书号$value 已经授权了");
                    } else {
                        //查询书籍进行添加
                        $mybok = $book->where(array('book_id' => $value))->find();
                        unset($mybok['book_id']); //删除书籍表中的关联值
                    //   if($mybok[is_show]==1){
                            $mybok['fu_web'] = $mybok[web_id];
                            $mybok['web_id'] = $_POST[web_id];
                            $bookid = $book->add($mybok);
                            // //添加榜单
                            $bang->add(array('book_id' => $bookid));
                     // }
                    }
                }
                $this->success("授权结束");
            } else {
                $this->error("没有站点");
            }
        }
    }
    //导出所有书籍
    public function bookDump() {
        $where['fu_web'] = 0;
        $where['is_show'] = 1; //显示
        $where['words'] = array('gt', 0); //字数大于0
        $where['chapter'] = array('gt', 0); //章节大于0
        $where['cp_name'] = array('neq', '阅庭'); //阅庭第三方不给予授权
        $where['fu_book'] = array('not in', '3922,7967'); //单独的书
        A('Booklei')->bookDump($where, "授权代理作品");
    }
}
