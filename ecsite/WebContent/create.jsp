<%@ page language="java" contentType="text/html; charset=UTF-8"
    pageEncoding="UTF-8"%>
<%@ taglib prefix="s" uri="/struts-tags" %>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta http-equiv="Content-Style-Type" content="text/css"/>
	<meta http-equiv="Content-Script-Type" content="text/javascript"/>
	<meta http-equiv="imagetoolbar" content="no"/>
	<meta name="description" content=""/>
	<meta name="keywords" content=""/>
	<title>管理者画面</title>
	<style type="text/css">
		body{
		margin: 0;
		padding: 0;
		line-height: 1.6;
		letter-spacing: 1px;
		font-family: Verdana,Helvetica,sans-serif;
		font-size: 12px;
		color: #333;
		background: #fff;
		}
		table{
		text-align: center;
		margin: 0 auto;
		}

		#top{
		width: 500px;
		margin: 30px auto;
		border: 1px solid #333;
		}
		#left p{
		color: red;
		}
		#right p{
		color: blue;
		}
		#header{
		width: 100%;
		height: 80px;
		background-color: black;
		}
		#main{
		width: 100%;
		height: 500px;
		text-align: center;
		}
		#footer{
		width: 100%;
		height: 80px;
		background-color: black;
		clear: both;
		}
	</style>
</head>
<body>
	<div id="header">
		<div id="pr">
		</div>
	</div>
	<div id="main">

		<div id="left">
		<div id="top">
			<p>商品</p>
		</div>
			<%-- <div>
				<p>商品情報を<a href='<s:url action="ItemCreateAction"/>'>追加</a></p>
			</div>
			<div>
				<p>商品情報<a href='<s:url action="ItemListAction"/>'>一覧</a></p>
			</div> --%>
			<a href='<s:url action="ItemCreateAction"><s:param name="id" value="%{id}"/></s:url>'><input type="button" value="追加"></a>
			<br>
			<br>
			<a href='<s:url action="ItemListAction"><s:param name="id" value="%{id}"/></s:url>'><input type="button" value="一覧"></a>
		</div>

		<div id="right">
		<div id="top">
			<p>ユーザー</p>
		</div>
			<%-- <div>
				<p>ユーザー情報を<a href='<s:url action="UserCreateAction"/>'>追加</a></p>
			</div>
			<div>
				<p>ユーザー情報<a href='<s:url action="UserListAction"/>'>一覧</a></p>
			</div> --%>
					<a href='<s:url action="UserCreateAction"><s:param name="id" value="%{id}"/></s:url>'><input type="button" value="追加"></a>
			<br>
			<br>
			<a href='<s:url action="UserListAction"><s:param name="id" value="%{id}"/></s:url>'><input type="button" value="一覧"></a>

			<br>
			<br>
			<div>
				<span>前画面に戻る場合は</span>
				<a href='<s:url action="HomeAction"/>'>こちら</a>
			</div>
		</div>

	</div>
	<div id="footer">
		<div id="pr">
		</div>
	</div>
</head>
<body>

</body>
</html>