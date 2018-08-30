package com.internousdev.ecsite.action;

import com.opensymphony.xwork2.ActionSupport;
import org.apache.struts2.interceptor.SessionAware;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Map;
import com.internousdev.ecsite.dao.ItemListDAO;
import com.internousdev.ecsite.dto.ItemListDTO;

public class ItemListAction extends ActionSupport implements SessionAware{
	public Map<String,Object> session;
	private ItemListDAO itemListDAO=new ItemListDAO();
	private ArrayList<ItemListDTO> itemList=new ArrayList<ItemListDTO>();
	private String deleteFlg;
	private String message;

	public String execute() throws SQLException{
		String result;
		if(!session.containsKey("id")){
			result= ERROR;
		}

		if(deleteFlg==null){

			itemList=itemListDAO.getItemInfo();
			session.put("itemList", itemList);
		}else if(deleteFlg.equals("1")){
			delete();
		}

		result=SUCCESS;
		return result;
	}

	public void delete() throws SQLException{
		String item_name=session.get("itemName").toString();
		String item_price=session.get("itemPrice").toString();
		String item_stock=session.get("itemStock").toString();
		int res=itemListDAO.itemHistoryDelete(item_name,item_price,item_stock);

		if(res>0){
			itemList=null;
			setMessage("商品情報を正しく削除しました。");
		}else if(res==0){
			setMessage("商品情報の削除に失敗しました。");
		}
	}

	public void setDeleteFlg(String deleteFlg){
		this.deleteFlg=deleteFlg;
	}
	@Override
	public void setSession(Map<String,Object> session){
		this.session=session;
	}
	public ArrayList<ItemListDTO> getItemList(){
		return this.itemList;
	}
	public String getMessage(){
		return this.message;
	}
	public void setMessage(String message){
		this.message=message;
	}


}
