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
	<title>UserList</title>
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
		width: 780px;
		margin: 30px auto;
		border: 1px solid #333;
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
		#text-right{
		display: inline-block;
		text-align: right;
		}
	</style>
</head>
<body>
	<div id="header">
		<div id="pr">
		</div>
	</div>
	<div id="main">
		<div id="top">
			<p>UserList</p>
		</div>
		<div>
		<s:if test="userList==null">
			<h3>登録ユーザーはおりません。</h3>
		</s:if>
		<s:elseif test="message==null">
			<h3>ユーザーリストは以下になります。</h3>
			<table border="1">
			<tr>
				<th>ID</th>
				<th>ユーザーID</th>
				<th>ユーザーPASS</th>
				<th>ユーザー名</th>
				<th>追加日</th>
			</tr>
			<s:iterator value="#session.userList">
				<tr>
					<td><s:property value="id"/></td>
					<td><s:property value="loginId"/></td>
					<td><s:property value="loginPass"/></td>
					<td><s:property value="userName"/></td>
					<td><s:property value="insert_date"/></td>
					<td><a href='<s:url action="UserDetailAction"><s:param name="id" value="%{id}"/></s:url>'><input type="button" value="詳細"></a></td>
				</tr>
			</s:iterator>
			</table>
			<s:form action="ItemListAction">
				<input type="hidden" name="deleteFlg" value="1">
				<s:submit value="全削除" method="delete"/>
			</s:form>
		</s:elseif>
		<s:if test="message!=null">
			<h3><s:property value="message"/></h3>
		</s:if>
			<div>
				<br>
				<span>前画面に戻る場合は</span>
				<a href='<s:url action="CreateAction"/>'>こちら</a>
			</div>
		</div>
	</div>
	<div id="footer">
		<div id="pr">
		</div>
	</div>
</body>
</html>