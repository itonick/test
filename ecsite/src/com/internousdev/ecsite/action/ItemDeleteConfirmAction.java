package com.internousdev.ecsite.action;

import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Map;

import org.apache.struts2.interceptor.SessionAware;

import com.internousdev.ecsite.dao.ItemDeleteConfirmDAO;
import com.internousdev.ecsite.dto.MyPageDTO;
import com.opensymphony.xwork2.ActionSupport;

public class ItemDeleteConfirmAction extends ActionSupport implements SessionAware{
	public Map<String,Object> session;
	private ItemDeleteConfirmDAO itemDeleteConfirmDAO=new ItemDeleteConfirmDAO();
	private ArrayList<ItemDeleteConfirmDTO> itemDeleteConfirm=new ArrayList<ItemDeleteConfirmDTO>();
	private String deleteFlg;
	private String message;

	public void delete() throws SQLException{
		String id=session.get("id").toString();
		String item_name=session.get("item_name").toString();
		String item_price=session.get("item_price").toString();
		String item_stock=session.get("item_stock").toString();

		int res=ItemDeleteConfirmDAO.itemDelete(id,item_name,item_price,item_stock);

		if(res>0){
			itemDeleteConfirmDAO=null;
			setMessage("商品情報を正しく削除しました。");
		}else if(res==0){
			setMessage("商品情報の削除に失敗しました。");
		}
	}

	public void setDeleteFlg(String deleteFlg){
		this.deleteFlg=deleteFlg;
	}
	public String getMessage(){
		return this.message;
	}
	public void setMessage(String message){
		this.message=message;
	}


}
