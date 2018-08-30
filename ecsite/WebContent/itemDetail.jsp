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
	<title>ItemDetail</title>
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
			<p>ItemDetail</p>
		</div>
		<div>
<%-- 		<s:if test="ItemDetailDTO==null">
			<h3>ご購入情報はありません。</h3>
		</s:if> --%>
		<s:if test="message==null">
			<h3>商品詳細は以下になります。</h3>
			<table border="1">
			<tr>
				<th>No.</th>
				<th>商品名</th>
				<th>値段</th>
				<th>個数</th>
				<th>追加日</th>
			</tr>
				<tr>
					<td><s:property value="%{#session.id}"/><s:param name="id" value="%{id}"/></td>
					<td><s:property value="%{#session.itemName}"/></td>
					<td><s:property value="%{#session.itemPrice}"/><span>円</span></td>
					<td><s:property value="%{#session.itemStock}"/><span>個</span></td>
					<td><s:property value="%{#session.insert_date}"/></td>
				</tr>
			</table>
			<s:form action="UserDetailAction">
				<input type="hidden" name="deleteFlg" value="1">
				<s:submit value="変更" method="delete"/>
			</s:form>
			<s:form action="ItemDeleteAction">
				<input type="hidden" name="deleteFlg" value="1">
				<s:submit value="削除" method="delete"/>
			</s:form>
		</s:if>
		<%-- <s:if test="message!=null">
			<h3><s:property value="message"/></h3>
		</s:if> --%>
		<br>
		<div id="text-right">
			<p>前画面へ<a href='<s:url action="ItemListAction"/>'>戻る</a></p>
		</div>
		</div>

 	</div>
	<div id="footer">
		<div id="pr">
		</div>
	</div>
</body>
</html>