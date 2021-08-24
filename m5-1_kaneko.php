<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset = "utf-8">
    </head>
    <body>
        
        <?php
        //二重送信防止機能実装ver
        //↓二重投稿禁止機能は以下を参照(一部参照渡しが使われていたが、PHPでは非推奨)
        //https://plus-work.jp/php/post_nizyu.php
        session_start();//セッションスタート
        
        //データベース接続
        $dsn = 'データベース名';
        $user = 'ユーザ名';
        $password = 'パスワード名';
        $pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

        //テーブル作成・表示
        $sql = "CREATE TABLE IF NOT EXISTS test1"
        ."("
        ."id INT AUTO_INCREMENT PRIMARY KEY,"
        ."name char(32),"
        ."comment TEXT,"
        ."date DATETIME,"
        ."password char(32)"
        .");";
        $stmt = $pdo->query($sql);

        //テーブルの行数取得関数
        function count_row(){
            global $pdo; //($pdoを関数内で使えるように設定)https://1-notes.com/functions-mysql/
            $sql = 'SELECT * from test1';//テーブル全取得
            $stmt = $pdo -> query($sql);
            $cnt = $stmt -> rowCount();//テーブル行数取得
            return $cnt;
        }
        
        //パスワードの正誤判定関数
        function is_correct($password, $id){
            global $pdo;
            $sql = 'SELECT * from test1 WHERE id=:id AND password=:password';
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindParam(':id', $id, PDO::PARAM_STR);
            $stmt -> bindParam(':password', $password, PDO::PARAM_STR);
            $stmt -> execute();
            $cnt = $stmt -> rowCount();//SQL実行後の行数取得
            
            if($cnt == 1){//該当件数が1件であれば正しいと判定
                return 1;
            }else{
                return 0;
            }
        }

        /*日付型と時刻型について↓
        https://www.dbonline.jp/mysql/type/index4.html
        */

        //入力処理
        if(isset($_POST['token']) && isset($_SESSION['token']) && ($_POST['token'] == $_SESSION['token'])){//二重送信防止
            if(isset($_POST['name']) && isset($_POST['comment']) && isset($_POST['password']) && isset($_POST['re_num'])){
                $name = $_POST['name'];
                $comment = $_POST['comment'];
                $password = $_POST['password'];
                $id = $_POST['re_num'];//新規投稿か編集か判断
                $date = date("Y/m/d H:i:s");
                if(($name!="") && ($comment != "") && ($password != "") && ($id != "")){//編集の場合
                    if(!(is_correct($password, $id))){
                        echo "パスワードが違います<br>";
                    }
                    else{
                        $sql = 'UPDATE test1 SET name=:name, comment=:comment, date=:date WHERE id=:id AND password=:password';
                        $stmt = $pdo -> prepare($sql);
                        $stmt -> bindParam(':name', $name, PDO::PARAM_STR);
                        $stmt -> bindParam(':comment', $comment, PDO::PARAM_STR);
                        $stmt -> bindParam(':date', $date, PDO::PARAM_STR);
                        $stmt -> bindParam(':id', $id, PDO::PARAM_STR);
                        $stmt -> bindParam(':password', $password, PDO::PARAM_STR);
                        $stmt -> execute();
                        echo "$id"."番を編集しました<br>";//デバッグ用
                    }
                }
                else if(($name != "") && ($comment != "") && ($password != "")){ //新規投稿の場合
                    
                    //テーブルに値を挿入
                    $sql = 'INSERT INTO test1(name, comment, date, password) VALUES(:name, :comment, :date, :password)';
                    $stmt = $pdo->prepare($sql);
                    $stmt -> bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt -> bindParam(':comment', $comment, PDO::PARAM_STR);
                    $stmt -> bindParam(':date', $date, PDO::PARAM_STR);
                    $stmt -> bindParam(':password', $password, PDO::PARAM_STR);
                    $stmt -> execute();
                    echo "新規投稿完了です<br>";
                }else{
                    echo "未入力の項目があります<br>";
                }
            }

            //削除処理
            if(isset($_POST['d_num']) && isset($_POST['password'])){
                $id = $_POST['d_num'];
                $password = $_POST['password'];
                
                if(($id != "") && (($id > count_row()) || ($id <= 0))){//番号に該当する投稿無し
                    echo "番号に該当する投稿はありません<br>";
                }else if(($id != "") && ($password != "")){//値が入っている時
                    if(!(is_correct($password, $id))){
                        echo "パスワードが違います<br>";
                    }else{
                        $sql = 'DELETE from test1 where id=:id AND password=:password';
                        $stmt = $pdo -> prepare($sql);
                        $stmt -> bindParam(':id', $id, PDO::PARAM_STR);
                        $stmt -> bindParam(':password', $password, PDO::PARAM_STR);
                        $stmt -> execute();
        
                        //投稿番号を再度振り直す
                        //https://www.searchlight8.com/mysql-auto-increment-renew/
                        $sql = 'ALTER table test1 drop column id';//idカラムを削除
                        $stmt = $pdo->query($sql);
                        $sql = 'ALTER table test1 add id INT PRIMARY KEY AUTO_INCREMENT first';//idカラムを再度作成
                        $stmt = $pdo -> query($sql);
                        echo "削除を完了しました<br>";
                    }
                }else{//数字が入力されていない場合
                    echo "未記入項目があります<br>";
                }
            }

            //編集処理
            if((isset($_POST['e_num'])) && (isset($_POST['password']))){
                $id = $_POST['e_num'];
                $password = $_POST['password'];
                if(($id != "") && (($id > count_row()) || ($id <= 0))){//編集番号が投稿にない場合
                    $id = "";//e_numを初期化
                    echo "番号に該当する投稿はありません<br>";
                }
                else if(($id != "") && ($password != "")){
                    if(!(is_correct($password, $id))){
                        echo "パスワードが違います<br>";
                    }
                    else{
                        $sql = 'SELECT id, name, comment from test1 WHERE id=:id AND password=:password';
                        $stmt = $pdo -> prepare($sql);
                        $stmt -> bindParam(':id', $id, PDO::PARAM_STR);
                        $stmt -> bindParam(':password', $password, PDO::PARAM_STR);
                        $stmt -> execute();
                        $results = $stmt -> fetchAll();
                        $cnt = $stmt -> rowCount();
                        foreach($results as $result){
                            $name = $result['name'];
                            $comment = $result['comment'];
                            $id = $result['id'];
                        }
                        echo "$id"."番を編集します<br>";//チェック用
                    }
                }
                else{
                    echo "数字が入力されていません<br>";
                }
            }
        }
        $token = md5(uniqid(rand(), true));
        $_SESSION['token'] = $token;
        //↓md5やuniqidについて
        //https://amatou-papa.com/php-uniqid/
        ?>

        <h4>入力フォーム</h4>
        <form action = "" method = "POST">
            <input type="hidden" name="token" value="<?php echo $token;?>">
            名前：<input type="text" name="name" value="<?php if(isset($name) && isset($_POST['edit']))echo $name; ?>" placeholder="氏名"><br>
            コメント：<input type="text" name="comment" value="<?php if(isset($comment) && isset($_POST['edit']))echo $comment; ?>" placeholder="コメント"><br>
            パスワード：<input type="password" name="password" value="" placeholder="パスワード">
            <input type="hidden" name="re_num" value="<?php if(isset($id)) echo $id; ?>">
            <input type="submit" value="送信">
        </form>

        <h4>削除フォーム</h4>
        <form action = "" method = "POST">
            <input type="hidden" name="token" value="<?php echo $token;?>">
            削除番号：<input type = "number" name="d_num" placeholder="数字"><br>
            パスワード：<input type="password" name="password" value="" placeholder="パスワード">
            <input type = "submit" name="delete" value="削除">
        </form>

        <h4>編集フォーム</h4>
        <form action = "" method = "POST">
            <input type="hidden" name="token" value="<?php echo $token;?>">
            編集投稿番号：<input type="number" name="e_num" placeholder="数字"><br>
            パスワード：<input type="password" name="password" value="" placeholder="パスワード">
            <input type="submit" name="edit"  value="編集">
        </form>

        <?php
        //テーブルの中身を表示
        $sql = 'SELECT * from test1';
        $stmt = $pdo -> query($sql);
        $results = $stmt -> fetchAll();
        foreach($results as $row){
            echo $row['id'].' ';
            echo $row['name'].' ';
            echo $row['comment'].' ';
            echo $row['date'].' ';
            echo "<br>";
            echo "<hr>";
        }
        ?>
    </body>
</html>

