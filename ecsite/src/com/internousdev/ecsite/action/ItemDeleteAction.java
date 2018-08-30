package com.internousdev.ecsite.action;

import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Map;

import org.apache.struts2.interceptor.SessionAware;

import com.internousdev.ecsite.dao.ItemDeleteDAO;
import com.internousdev.ecsite.dto.ItemDeleteDTO;
import com.opensymphony.xwork2.ActionSupport;

public class ItemDeleteAction extends ActionSupport implements SessionAware{
	public Map<String,Object> session;
	private ItemDeleteDAO itemDeleteDAO=new ItemDeleteDAO();
	private ArrayList<ItemDeleteDTO> itemDeleteList=new ArrayList<ItemDeleteDTO>();
	private String deleteFlg;
	private String message;
	public void delete() throws SQLException{
		String item_name=session.get("itemName").toString();
		String item_price=session.get("itemPrice").toString();
		String item_stock=session.get("itemStock").toString();
		int res=itemDeleteDAO.itemDelete(item_name,item_price,item_stock);

		if(res>0){
			itemDeleteList=null;
			setMessage("商品情報を正しく削除しました。");
		}else if(res==0){
			setMessage("商品情報の削除に失敗しました。");
		}
	}
	public Map<String, Object> getSession() {
		return session;
	}
	public void setSession(Map<String, Object> session) {
		this.session = session;
	}

	public ItemDeleteDAO getItemDeleteDAO() {
		return itemDeleteDAO;
	}
	public void setItemDeleteDAO(ItemDeleteDAO itemDeleteDAO) {
		this.itemDeleteDAO = itemDeleteDAO;
	}
	public ArrayList<ItemDeleteDTO> getItemDeleteList() {
		return itemDeleteList;
	}
	public void setItemDeleteList(ArrayList<ItemDeleteDTO> itemDeleteList) {
		this.itemDeleteList = itemDeleteList;
	}
	public String getDeleteFlg() {
		return deleteFlg;
	}
	public void setDeleteFlg(String deleteFlg) {
		this.deleteFlg = deleteFlg;
	}
	public String getMessage() {
		return message;
	}
	public void setMessage(String message) {
		this.message = message;
	}


}
