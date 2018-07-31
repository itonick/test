package jp.co.internous.action;

public class Test {
	public static void main(String[] args){
		Robot aibo=new Robot();
		aibo.name="aibo";
		System.out.println(aibo.name);
		aibo.talk();
		aibo.walk();
		aibo.run();
		
		Robot asimo=new Robot();
		asimo.name="asimo";
		System.out.println(asimo.name);
		asimo.talk();
		asimo.walk();
		asimo.run();
		
		Robot pepper=new Robot();
		pepper.name="pepper";
		System.out.println(pepper.name);
		pepper.talk();
		pepper.walk();
		pepper.run();
		
		Robot doraemon=new Robot();
		doraemon.name="doraemon";
		System.out.println(doraemon.name);
		doraemon.talk();
		doraemon.walk();
		doraemon.run();
	}

}
